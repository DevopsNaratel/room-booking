pipeline {
    agent any

    environment {
        // --- App Metadata ---
        APP_NAME        = "sybau"
        DOCKER_IMAGE    = "devopsnaratel/diwapp"
        APP_VERSION     = ""
        
        // --- WebUI & API Config ---
        WEBUI_API       = "http://localhost:3002"
        
        // --- Repository & Credentials ---
        GITOPS_REPO     = "https://github.com/DevopsNaratel/Deployment-Manifest-App.git"
        GITOPS_BRANCH   = "main"
        GIT_CRED_ID     = "git-token"
        DOCKER_CRED_ID  = "docker-hub"
        
        // --- External Tools ---
        SONAR_SERVER_ID = "sonarqube-server"
    }

    stages {
        stage('Checkout & Get Version') {
            steps {
                script {
                    checkout scm
                    APP_VERSION = sh(
                        script: "git describe --tags --always --abbrev=0 || echo ${BUILD_NUMBER}", 
                        returnStdout: true
                    ).trim()
                    echo "🚀 Building Version: ${APP_VERSION}"
                }
            }
            post {
                success { echo "✅ [CHECKOUT] Source code ready. Version: ${APP_VERSION}" }
                failure { echo "❌ [CHECKOUT] Failed to fetch source code." }
            }
        }

        stage('Security: Gitleaks Scan') {
            steps {
                script {
                    sh """
                        GITLEAKS_TMP="/tmp/gitleaks_tool"
                        mkdir -p \$GITLEAKS_TMP
                        if [ ! -f "\$GITLEAKS_TMP/gitleaks" ]; then
                            curl -sfL https://github.com/gitleaks/gitleaks/releases/download/v8.18.2/gitleaks_8.18.2_linux_x64.tar.gz | tar -xz -C \$GITLEAKS_TMP
                            chmod +x \$GITLEAKS_TMP/gitleaks
                        fi
                        \$GITLEAKS_TMP/gitleaks detect --source . --no-git --exit-code 1 -v
                    """
                }
            }
            post {
                success { echo "✅ [GITLEAKS] No secrets leaked." }
                failure { echo "❌ [GITLEAKS] Secrets detected in source code!" }
            }
        }

        stage('Security: SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'sonar-scanner'
                    withSonarQubeEnv("${SONAR_SERVER_ID}") {
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${APP_NAME} -Dsonar.sources=."
                    }
                }
            }
            post {
                failure { echo "❌ [SONARQUBE] Analysis submission failed." }
            }
        }

        stage('Security: Quality Gate') {
            steps {
                script {
                    timeout(time: 5, unit: 'MINUTES') {
                        def qg = waitForQualityGate()
                        if (qg.status != 'OK') {
                            error "Pipeline stopped: Quality Gate Failed (${qg.status})"
                        }
                    }
                }
            }
            post {
                success { echo "✅ [QUALITY GATE] Code quality passed." }
                failure { echo "❌ [QUALITY GATE] Code quality failed." }
            }
        }

        stage('Security: Trivy Library Scan') {
            steps {
                script {
                    sh """
                        TRIVY_DIR="/tmp/trivy_tool"
                        mkdir -p \$TRIVY_DIR
                        if [ ! -f "\$TRIVY_DIR/trivy" ]; then
                            curl -sfL https://github.com/aquasecurity/trivy/releases/download/v0.48.3/trivy_0.48.3_Linux-64bit.tar.gz | tar -xz -C \$TRIVY_DIR
                            chmod +x \$TRIVY_DIR/trivy
                        fi
                        \$TRIVY_DIR/trivy fs --exit-code 1 --severity HIGH,CRITICAL --scanners vuln --format table .
                    """
                }
            }
            post {
                success { echo "✅ [TRIVY SCA] Libraries are secure." }
                failure { echo "❌ [TRIVY SCA] Vulnerabilities found in libraries!" }
            }
        }

        stage('Build & Push Docker Image') {
            steps {
                script {
                    docker.withRegistry('', "${DOCKER_CRED_ID}") {
                        def customImage = docker.build("${DOCKER_IMAGE}:${APP_VERSION}")
                        customImage.push()
                        customImage.push("latest")
                    }
                }
            }
            post {
                success { echo "✅ [DOCKER] Image pushed: ${DOCKER_IMAGE}:${APP_VERSION}" }
                failure { echo "❌ [DOCKER] Build/Push failed." }
            }
        }

        stage('Security: Trivy Image Scan') {
            steps {
                script {
                    sh """
                        docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
                        aquasec/trivy:latest image \
                        --exit-code 1 --severity CRITICAL --scanners vuln,secret \
                        ${DOCKER_IMAGE}:${APP_VERSION}
                    """
                }
            }
            post {
                success { echo "✅ [TRIVY IMAGE] Docker image is clean (No CRITICAL vuln)." }
                failure { echo "❌ [TRIVY IMAGE] Critical vulnerabilities found in image!" }
            }
        }

        stage('Deploy to Testing (GitOps + WebUI)') {
            steps {
                script {
                    // 1. Update GitOps Manifest
                    syncManifest("testing", APP_NAME, DOCKER_IMAGE, APP_VERSION, 1)
                    
                    // 2. Trigger WebUI for Ephemeral Environment
                    echo "🔗 Triggering WebUI Ephemeral Environment..."
                    def response = sh(script: """
                        curl -s -X POST ${WEBUI_API}/api/jenkins/deploy-test \
                        -H "Content-Type: application/json" \
                        -d '{"appName": "${APP_NAME}", "imageTag": "${APP_VERSION}"}'
                    """, returnStdout: true).trim()

                    echo "WebUI Response: ${response}"
                    if (response.contains('"error"')) { error "WebUI Deploy Error: ${response}" }
                    
                    echo "Waiting for pods to be ready..."
                    sleep 60
                }
            }
            post {
                success { echo "✅ [DEPLOY TESTING] Environment ready & GitOps synced." }
            }
        }

        stage('Approval for Production') {
            steps {
                script {
                    echo "⏳ Menunggu persetujuan untuk Production..."
                    try {
                        input message: "Approve deploy ${APP_VERSION} ke Prod?", id: 'ApproveDeploy'
                    } catch (Exception e) {
                        currentBuild.result = 'ABORTED'
                        error "Deployment dibatalkan."
                    }
                }
            }
        }

        stage('Deploy to Production (GitOps + WebUI)') {
            steps {
                script { 
                    // 1. Update GitOps Manifest
                    syncManifest("prod", APP_NAME, DOCKER_IMAGE, APP_VERSION, 1)

                    // 2. Notify WebUI for Manifest Update
                    echo "🔗 Notifying WebUI for Production Update..."
                    def response = sh(script: """
                        curl -s -X POST ${WEBUI_API}/api/manifest/update-image \
                        -H "Content-Type: application/json" \
                        -d '{"appName": "${APP_NAME}", "env": "prod", "imageTag": "${APP_VERSION}"}'
                    """, returnStdout: true).trim()

                    echo "WebUI Response: ${response}"
                    if (response.contains('"error"')) { error "WebUI Prod Update Error: ${response}" }
                }
            }
            post {
                success { echo "🚀 [DEPLOY PROD] Release version ${APP_VERSION} is LIVE." }
                failure { echo "❌ [DEPLOY PROD] Production release failed." }
            }
        }
    }

    post {
        always {
            script {
                echo "🧹 Cleaning up Ephemeral Testing Environment..."
                sh """
                    curl -s -X POST ${WEBUI_API}/api/jenkins/destroy-test \
                    -H "Content-Type: application/json" \
                    -d '{"appName": "${APP_NAME}"}'
                """
                cleanWs()
            }
        }
    }
}

// --- Shared Functions ---
def syncManifest(envName, appName, imageRepo, imageTag, replicas) {
    def targetFolder = "apps/${appName}-${envName}"
    def gitRepoPath  = "gitops-${envName}-${envName.hashCode().toString().take(5)}"

    dir(gitRepoPath) {
        deleteDir()
        checkout([$class: 'GitSCM',
            branches: [[name: "${GITOPS_BRANCH}"]],
            userRemoteConfigs: [[url: "${GITOPS_REPO}", credentialsId: "${GIT_CRED_ID}"]]
        ])

        if (fileExists(targetFolder)) {
            def valFile = "${targetFolder}/values.yaml"
            sh """
                sed -i "s|tag: ['\\\"].*['\\\"]|tag: '${imageTag}'|" ${valFile}
                if grep -q '^replicaCount:' ${valFile}; then
                    sed -i 's|^replicaCount: .*|replicaCount: ${replicas}|' ${valFile}
                else
                    echo 'replicaCount: ${replicas}' >> ${valFile}
                fi
                if [ "${envName}" == "testing" ]; then
                    sed -i '/^[[:space:]]*type: ClusterIP/d' ${valFile}
                    if ! grep -q 'type: NodePort' ${valFile}; then
                        sed -i '/^service:/a \\  type: NodePort' ${valFile}
                    fi
                    sed -i '/^ingress:/,/^[^ ]/ s/enabled: true/enabled: false/' ${valFile}
                fi
            """
            withCredentials([usernamePassword(credentialsId: "${GIT_CRED_ID}", usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
                def remoteUrl = GITOPS_REPO.replace("https://", "https://${GIT_USER}:${GIT_PASS}@")
                sh """
                    git config user.email "jenkins@naratel.id"
                    git config user.name "Jenkins Pipeline"
                    git add .
                    if ! git diff-index --quiet HEAD; then
                        git commit -m "ci: release ${appName} ${envName} version ${imageTag}"
                        git pull --rebase ${remoteUrl} ${GITOPS_BRANCH}
                        git push ${remoteUrl} HEAD:${GITOPS_BRANCH}
                    fi
                """
            }
        } else {
            error "❌ Folder ${targetFolder} tidak ditemukan!"
        }
    }
}
