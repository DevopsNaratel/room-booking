pipeline {
    agent any

    environment {
        APP_NAME       = "room-booking" 
        DOCKER_IMAGE   = "devopsnaratel/room-booking"
        GITOPS_REPO    = "https://github.com/DevopsNaratel/Deployment-Manifest-App.git"
        GITOPS_BRANCH  = "main"
        GIT_CRED_ID    = "git-token" 
        DOCKER_CRED_ID = "docker-hub"
        APP_VERSION    = ""
        // Tambahkan referensi nama server SonarQube yang ada di Jenkins System Config
        SONAR_SERVER_ID = "sonarqube-server" 
    }

    stages {
        stage('Checkout & Get Version') {
            steps {
                script {
                    checkout scm
                    APP_VERSION = sh(script: "git describe --tags --always --abbrev=0 || echo ${BUILD_NUMBER}", returnStdout: true).trim()
                    echo "Aplikasi akan di-build dengan versi: ${APP_VERSION}"
                }
            }
        }

        // --- STAGE SONARQUBE MULAI DI SINI ---
        stage('SonarQube Analysis') {
            steps {
                script {
                    // withSonarQubeEnv akan otomatis menginjeksi URL & Token dari Jenkins Config
                    withSonarQubeEnv("${SONAR_SERVER_ID}") {
                        // Untuk Laravel, gunakan sonar-scanner
                        // Pastikan sonar-scanner sudah terinstal di Jenkins Tools
                        sh """
                            sonar-scanner \
                            -Dsonar.projectKey=${APP_NAME} \
                            -Dsonar.sources=. \
                            -Dsonar.host.url=${SONAR_HOST_URL} \
                            -Dsonar.login=${SONAR_AUTH_TOKEN}
                        """
                    }
                }
            }
        }

        stage('Quality Gate') {
            steps {
                script {
                    // Pipeline akan berhenti di sini jika Quality Gate di SonarQube gagal (Fail)
                    // Memerlukan Webhook yang sudah dikonfigurasi di SonarQube ke Jenkins
                    timeout(time: 5, unit: 'MINUTES') {
                        def qg = waitForQualityGate()
                        if (qg.status != 'OK') {
                            error "Pipeline dihentikan karena Quality Gate Gagal: ${qg.status}"
                        }
                    }
                }
            }
        }
        // --- STAGE SONARQUBE SELESAI ---

        stage('Build & Push Docker Image') {
            // Stage ini hanya akan jalan jika Quality Gate Status adalah 'OK'
            steps {
                script {
                    docker.withRegistry('', "${DOCKER_CRED_ID}") {
                        def customImage = docker.build("${DOCKER_IMAGE}:${APP_VERSION}")
                        customImage.push()
                        customImage.push("latest")
                    }
                }
            }
        }

        stage('Deploy to Testing') {
            steps {
                script { 
                    syncManifest("testing", APP_NAME, DOCKER_IMAGE, APP_VERSION, 1) 
                }
            }
        }

        stage('Waiting for Approval') {
            steps {
                script {
                    echo "Menunggu persetujuan untuk Production..."
                    try {
                        input message: "Approve deploy ${APP_VERSION} ke Prod?", id: 'ApproveDeploy'
                    } catch (Exception e) {
                        currentBuild.result = 'ABORTED'
                        error "Deployment dibatalkan."
                    } finally {
                        echo "Scaling down Testing environment..."
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
        }
    }

    post {
        always { cleanWs() }
    }
}
// ... fungsi syncManifest tetap sama ...

def syncManifest(envName, appName, imageRepo, imageTag, replicas) {
    def targetFolder = "apps/${appName}-${envName}"
    def gitRepoPath = "gitops-${envName}-${envName.hashCode().toString().take(5)}"

    dir(gitRepoPath) {
        deleteDir() 
        checkout([$class: 'GitSCM', 
            branches: [[name: "${GITOPS_BRANCH}"]], 
            userRemoteConfigs: [[url: "${GITOPS_REPO}", credentialsId: "${GIT_CRED_ID}"]]
        ])

        if (fileExists(targetFolder)) {
            def valFile = "${targetFolder}/values.yaml"
            
            sh """
                # Update Image Tag: mendukung kutip tunggal/ganda
                sed -i "s|tag: ['\\\"].*['\\\"]|tag: '${imageTag}'|" ${valFile}
                
                # Update Replica Count
                if grep -q '^replicaCount:' ${valFile}; then
                    sed -i 's|^replicaCount: .*|replicaCount: ${replicas}|' ${valFile}
                else
                    echo 'replicaCount: ${replicas}' >> ${valFile}
                fi

                # Memastikan targetPort selalu 80 sesuai Dockerfile Anda
                sed -i 's|targetPort: .*|targetPort: 80|' ${valFile}

                # Konfigurasi NodePort khusus Testing
                if [ "${envName}" == "testing" ]; then
                    sed -i '/^[[:space:]]*type: ClusterIP/d' ${valFile}
                    if ! grep -q 'type: NodePort' ${valFile}; then
                        sed -i '/^service:/a \\  type: NodePort' ${valFile}
                    fi
                    # Disable Ingress for Testing to prevent conflicts
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
            error "Folder ${targetFolder} tidak ditemukan!"
        }
    }
}
