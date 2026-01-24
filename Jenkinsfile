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

        stage('Gitleaks Secret Scan') {
            steps {
                script {
                    sh """
                        # 1. Gunakan folder /tmp agar terpisah dari source code
                        GITLEAKS_TMP="/tmp/gitleaks_tool"
                        mkdir -p \$GITLEAKS_TMP
                        
                        # 2. Download dan ekstrak (pake v8.18.2 atau v8.30.0 oke saja)
                        if [ ! -f "\$GITLEAKS_TMP/gitleaks" ]; then
                            echo "Mengunduh Gitleaks..."
                            curl -sfL https://github.com/gitleaks/gitleaks/releases/download/v8.18.2/gitleaks_8.18.2_linux_x64.tar.gz | tar -xz -C \$GITLEAKS_TMP
                            # Tambahkan izin eksekusi untuk mencegah error 126
                            chmod +x \$GITLEAKS_TMP/gitleaks
                        fi
                        
                        # 3. Jalankan scan pada folder saat ini (.)
                        # Tanpa --exclude-path karena binary sudah di luar folder kerja
                        \$GITLEAKS_TMP/gitleaks detect --source . --no-git --exit-code 1 -v
                    """
                }
            }
        }
                
        stage('SonarQube Analysis') {
            steps {
                script {
                    def scannerHome = tool 'sonar-scanner'
                    withSonarQubeEnv("${SONAR_SERVER_ID}") {
                        // Sederhanakan! Host & Login sudah dihandle oleh plugin
                        sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=${APP_NAME} -Dsonar.sources=."
                    }
                }
            }
        }

        stage('Trivy Library Scan (SCA)') {
            steps {
                script {
                    sh """
                        # 1. Setup folder tool
                        TRIVY_DIR="/tmp/trivy_tool"
                        mkdir -p \$TRIVY_DIR
                        
                        # 2. Download binary secara manual (Pilih versi v0.48.3 yang stabil)
                        if [ ! -f "\$TRIVY_DIR/trivy" ]; then
                            echo "Mengunduh Trivy binary..."
                            # Mengunduh tarball langsung sesuai arsitektur Linux 64-bit
                            curl -sfL https://github.com/aquasecurity/trivy/releases/download/v0.48.3/trivy_0.48.3_Linux-64bit.tar.gz | tar -xz -C \$TRIVY_DIR
                            chmod +x \$TRIVY_DIR/trivy
                        fi
                        
                        # 3. Jalankan scan
                        echo "Menjalankan pemindaian library..."
                        \$TRIVY_DIR/trivy fs --exit-code 1 --severity HIGH,CRITICAL --scanners vuln --format table .
                    """
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

        stage('Trivy Security Scan') {
            steps {
                script {
                    echo "Scanning Image using Dockerized Trivy..."
                    // Kita jalankan container trivy yang meminjam docker.sock dari host
                    sh """
                        docker run --rm -v /var/run/docker.sock:/var/run/docker.sock \
                        aquasec/trivy:latest image \
                        --exit-code 1 \
                        --severity CRITICAL \
                        --scanners vuln,secret \
                        ${DOCKER_IMAGE}:${APP_VERSION}
                    """
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
