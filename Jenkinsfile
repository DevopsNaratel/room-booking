pipeline {
    agent any

    environment {
        // --- App Metadata ---
        APP_NAME        = "room-booking"
        DOCKER_IMAGE    = "devopsnaratel/room-booking"
        APP_VERSION     = ""
        
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
                }
            }
            post {
                success { echo "✅ [CHECKOUT] Berhasil mengambil source code. Versi: ${APP_VERSION}" }
                failure { echo "❌ [CHECKOUT] Gagal melakukan git checkout." }
            }
        }

        stage('Gitleaks Secret Scan') {
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
                success { echo "✅ [GITLEAKS] Aman! Tidak ada secret (token/key) yang bocor." }
                failure { echo "❌ [GITLEAKS] Bahaya! Ditemukan potensi kebocoran secret di source code." }
            }
        }

        stage('SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'sonar-scanner'
                    withSonarQubeEnv("${SONAR_SERVER_ID}") {
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${APP_NAME} -Dsonar.sources=."
                    }
                }
            }
            post {
                success { echo "✅ [SONARQUBE] Analisis kode selesai dikirim ke server." }
                failure { echo "❌ [SONARQUBE] Gagal mengirim analisis ke SonarQube." }
            }
        }

        stage('Quality Gate') {
            steps {
                script {
                    timeout(time: 5, unit: 'MINUTES') {
                        def qg = waitForQualityGate()
                        if (qg.status != 'OK') {
                            error "Pipeline dihentikan karena Quality Gate Gagal: ${qg.status}"
                        }
                    }
                }
            }
            post {
                success { echo "✅ [QUALITY GATE] Lolos! Kode memenuhi standar kualitas." }
                failure { echo "❌ [QUALITY GATE] Tidak Lolos! Periksa laporan di Dashboard SonarQube." }
            }
        }

        stage('Trivy Library Scan (SCA)') {
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
                success { echo "✅ [TRIVY SCA] Tidak ditemukan kerentanan HIGH/CRITICAL pada library." }
                failure { echo "❌ [TRIVY SCA] Ditemukan kerentanan keamanan pada library aplikasi." }
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
                success { echo "✅ [DOCKER] Image ${DOCKER_IMAGE}:${APP_VERSION} berhasil dipush ke registry." }
                failure { echo "❌ [DOCKER] Gagal membangun atau melakukan push image." }
            }
        }

        stage('Trivy Security Scan') {
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
                success { echo "✅ [TRIVY IMAGE] Docker image dinyatakan aman dari kerentanan CRITICAL." }
                failure { echo "❌ [TRIVY IMAGE] Docker image mengandung celah keamanan kritikal!" }
            }
        }

        stage('Deploy to Testing') {
            steps {
                script { 
                    syncManifest("testing", APP_NAME, DOCKER_IMAGE, APP_VERSION, 1) 
                }
            }
            post {
                success { echo "✅ [DEPLOY TESTING] Manifest berhasil diupdate. ArgoCD akan mensinkronisasi ke K3s." }
                failure { echo "❌ [DEPLOY TESTING] Gagal mengupdate manifest testing." }
            }
        }

        stage('Waiting for Approval') {
            steps {
                script {
                    echo "⏳ Menunggu persetujuan user untuk lanjut ke Production..."
                    try {
                        input message: "Approve deploy ${APP_VERSION} ke Prod?", id: 'ApproveDeploy'
                    } catch (Exception e) {
                        currentBuild.result = 'ABORTED'
                        error "Deployment dibatalkan."
                    } finally {
                        echo "📉 Scaling down Testing environment..."
                        syncManifest("testing", APP_NAME, DOCKER_IMAGE, APP_VERSION, 0)
                    }
                }
            }
        }

        stage('Deploy to Production') {
            steps {
                script { 
                    syncManifest("prod", APP_NAME, DOCKER_IMAGE, APP_VERSION, 1) 
                }
            }
            post {
                success { echo "🚀 [DEPLOY PROD] Selesai! Aplikasi ${APP_VERSION}:${APP_VERSION} sudah dirilis ke Production." }
                failure { echo "❌ [DEPLOY PROD] Gagal melakukan update manifest Production." }
            }
        }
    }

    post {
        always {
            echo "🧹 Membersihkan workspace..."
            cleanWs()
        }
        success {
            echo "🎉 Pipeline selesai dengan sukses!"
        }
        failure {
            echo "🏮 Pipeline gagal. Silakan periksa detail log di atas."
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
                # Update Image Tag
                sed -i "s|tag: ['\\\"].*['\\\"]|tag: '${imageTag}'|" ${valFile}
                
                # Update Replica Count
                if grep -q '^replicaCount:' ${valFile}; then
                    sed -i 's|^replicaCount: .*|replicaCount: ${replicas}|' ${valFile}
                else
                    echo 'replicaCount: ${replicas}' >> ${valFile}
                fi

                # Testing Environment Specifics
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
