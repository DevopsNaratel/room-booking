pipeline {
    agent {
        kubernetes {
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
        APP_NAME           = 'room-booking'
        DOCKER_IMAGE       = "diwamln/${APP_NAME}" // Sesuai dengan repo DockerHub
        DOCKER_CREDS       = 'docker-hub'
        GIT_CREDS          = 'git-token'
        MANIFEST_REPO_URL  = 'github.com/DevopsNaratel/deployment-manifests.git'
        MANIFEST_DEV_PATH  = "${APP_NAME}/dev/deployment.yaml"
        MANIFEST_PROD_PATH = "${APP_NAME}/prod/deployment.yaml"
    }

    stages {
        stage('Checkout & Versioning') {
            steps {
                checkout scm
                script {
                    def commitHash = sh(returnStdout: true, script: "git rev-parse --short HEAD").trim()
                    env.BASE_TAG = "build-${BUILD_NUMBER}-${commitHash}"
                    currentBuild.displayName = "#${BUILD_NUMBER}-${env.BASE_TAG}"
                }
            }
        }

        stage('Build & Push Docker') {
            steps {
                container('docker') {
                    withCredentials([
                        usernamePassword(credentialsId: "${DOCKER_CREDS}", passwordVariable: 'DOCKER_PASSWORD', usernameVariable: 'DOCKER_USERNAME'),
                        usernamePassword(credentialsId: "${GIT_CREDS}", passwordVariable: 'GITHUB_TOKEN', usernameVariable: 'GIT_USER')
                    ]) {
                        sh """
                            echo ${DOCKER_PASSWORD} | docker login -u ${DOCKER_USERNAME} --password-stdin
                            
                            docker build \
                                --network=host \
                                --build-arg GITHUB_TOKEN=${GITHUB_TOKEN} \
                                -t ${DOCKER_IMAGE}:${env.BASE_TAG} .
                            
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
                script { updateManifest('dev', env.MANIFEST_DEV_PATH) }
            }
        }

        stage('Approval to PROD') {
            steps { 
                input message: "Cek Environment DEV. Lanjut ke PROD?", ok: "Deploy ke Prod" 
            }
        }

        stage('Update Manifest PROD') {
            steps {
                script { updateManifest('prod', env.MANIFEST_PROD_PATH) }
            }
        }
    }
    
    post {
        always { cleanWs() }
    }
}

// === Fungsi Update Manifest (Fixed Regex) ===
def updateManifest(envName, filePath) {
    withCredentials([usernamePassword(
        credentialsId: "${env.GIT_CREDS}", 
        passwordVariable: 'GIT_PASSWORD', 
        usernameVariable: 'GIT_USER'
    )]) {
        sh """
            git config --global user.email "jenkins@bot.com"
            git config --global user.name "Jenkins Bot"
            
            rm -rf temp_manifest_${envName}
            git clone https://${GIT_USER}:${GIT_PASSWORD}@${env.MANIFEST_REPO_URL} temp_manifest_${envName}
            cd temp_manifest_${envName}
            
            # Perbaikan Regex: Mencari teks yang mengandung nama image tanpa peduli ada docker.io atau tidak
            # Ini akan mengupdate initContainers dan containers sekaligus
            sed -i "s|image: .*${env.DOCKER_IMAGE}:.*|image: ${env.DOCKER_IMAGE}:${env.BASE_TAG}|g" ${filePath}
            
            # Cek apakah ada perubahan sebelum push
            if git diff --exit-code; then
                echo "No changes detected in ${filePath}. Check if the image name in YAML matches ${env.DOCKER_IMAGE}"
            else
                git add ${filePath}
                git commit -m "deploy: update ${env.APP_NAME} ${envName} to ${env.BASE_TAG} [skip ci]"
                git push origin main
            fi
        """
    }
}
