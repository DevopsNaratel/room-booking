pipeline {
    agent {
        kubernetes {
            // Definisi Pod Agent menggunakan Infrastructure as Code (Docker in Docker)
            yaml '''
apiVersion: v1
kind: Pod
spec:
  containers:
  - name: docker
    image: docker:24.0.6-dind
    securityContext:
      privileged: true
    volumeMounts:
    - name: dind-storage
      mountPath: /var/lib/docker
  - name: jnlp
    image: jenkins/inbound-agent:latest
  volumes:
  - name: dind-storage
    emptyDir: {}
'''
        }
    }

    environment {
        // --- KONFIGURASI DOCKER ---
        APP_NAME          = 'room-booking'
        DOCKER_IMAGE      = "diwamln/${APP_NAME}"
        DOCKER_CREDS      = 'docker-hub'
        
        // --- KONFIGURASI GIT (REPO MANIFEST) ---
        GIT_CREDS         = 'git-token'
        MANIFEST_REPO_URL = 'https://github.com/DevopsNaratel/deployment-manifests.git'
        
        // --- KONFIGURASI PATH MANIFEST ---
        MANIFEST_DEV_PATH  = "${APP_NAME}/dev/deployment.yaml"
        MANIFEST_PROD_PATH = "${APP_NAME}/prod/deployment.yaml"
    }

    stages {
        stage('Checkout & Versioning') {
            steps {
                checkout scm
                script {
                    // Membuat Tag Unik: build-[NOMOR_BUILD]-[GIT_HASH]
                    def commitHash = sh(returnStdout: true, script: "git rev-parse --short HEAD").trim()
                    env.BASE_TAG = "build-${BUILD_NUMBER}-${commitHash}"
                    currentBuild.displayName = "#${BUILD_NUMBER}-${env.BASE_TAG}"
                }
            }
        }

        stage('Build & Push Docker') {
            steps {
                container('docker') {
                    withCredentials([usernamePassword(
                        credentialsId: "${DOCKER_CREDS}", 
                        passwordVariable: 'DOCKER_PASSWORD', 
                        usernameVariable: 'DOCKER_USERNAME'
                    )]) {
                        sh """
                            docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD}
                            docker build -t ${DOCKER_IMAGE}:${env.BASE_TAG} .
                            docker push ${DOCKER_IMAGE}:${env.BASE_TAG}
                            docker tag ${DOCKER_IMAGE}:${env.BASE_TAG} ${DOCKER_IMAGE}:latest
                            docker push ${DOCKER_IMAGE}:latest
                        """
                    }
                }
            }
        }

        stage('Update Manifest DEV') {
            steps {
                script {
                    updateManifest('dev', env.MANIFEST_DEV_PATH)
                }
            }
        }

        stage('Approval to PROD') {
            steps {
                input message: "Promote ke PROD?", ok: "Yes, Deploy!"
            }
        }

        stage('Promote to PROD') {
            steps {
                script {
                    updateManifest('prod', env.MANIFEST_PROD_PATH)
                }
            }
        }
    }
    
    post {
        always {
            // Membersihkan workspace untuk efisiensi penyimpanan worker node
            cleanWs()
        }
    }
}

// Function reusable untuk update manifest GitOps
def updateManifest(envName, filePath) {
    withCredentials([usernamePassword(
        credentialsId: "${env.GIT_CREDS}", 
        passwordVariable: 'GIT_PASSWORD', 
        usernameVariable: 'GIT_USERNAME'
    )]) {
        sh """
            git config --global user.email "jenkins@bot.com"
            git config --global user.name "Jenkins Bot"
            
            rm -rf temp_manifest_${envName}
            git clone ${env.MANIFEST_REPO_URL} temp_manifest_${envName}
            cd temp_manifest_${envName}
            
            # Update tag image pada file deployment.yaml
            sed -i "s|image: ${env.DOCKER_IMAGE}:.*|image: ${env.DOCKER_IMAGE}:${env.BASE_TAG}|g" ${filePath}
            
            git add .
            git commit -m "deploy: update ${env.APP_NAME} to ${envName} using image ${env.BASE_TAG}" || true
            git push https://${GIT_USERNAME}:${GIT_PASSWORD}@github.com/DevopsNaratel/deployment-manifests.git main
        """
    }
}
