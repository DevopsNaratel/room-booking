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
        APP_NAME          = 'room-booking'
        DOCKER_IMAGE      = "diwamln/${APP_NAME}"
        DOCKER_CREDS      = 'docker-hub'
        GIT_CREDS         = 'git-token' // Pastikan ID ini sama dengan di Jenkins Credentials
        MANIFEST_REPO_URL = 'https://github.com/DevopsNaratel/deployment-manifests.git'
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
                    // Perbaikan: Menggunakan GIT_CREDS (bukan GIT_ID)
                    withCredentials([
                        usernamePassword(credentialsId: "${DOCKER_CREDS}", passwordVariable: 'DOCKER_PASSWORD', usernameVariable: 'DOCKER_USERNAME'),
                        usernamePassword(credentialsId: "${GIT_CREDS}", passwordVariable: 'GITHUB_TOKEN', usernameVariable: 'GIT_USER')
                    ]) {
                        sh """
                            docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD}
                            
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
            steps { input message: "Promote ke PROD?", ok: "Yes, Deploy!" }
        }

        stage('Promote to PROD') {
            steps {
                script { updateManifest('prod', env.MANIFEST_PROD_PATH) }
            }
        }
    }
    
    post {
        always { cleanWs() }
    }
}

def updateManifest(envName, filePath) {
    // Perbaikan: Menggunakan GIT_CREDS agar tidak null
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
            sed -i "s|image: ${env.DOCKER_IMAGE}:.*|image: ${env.DOCKER_IMAGE}:${env.BASE_TAG}|g" ${filePath}
            git add .
            git commit -m "deploy: update ${env.APP_NAME} to ${envName} image ${env.BASE_TAG}" || true
            git push https://${GIT_USERNAME}:${GIT_PASSWORD}@github.com/DevopsNaratel/deployment-manifests.git main
        """
    }
}
