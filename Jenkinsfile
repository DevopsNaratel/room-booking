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
        // --- DOCKER CONFIGURATION ---
        DOCKER_IMAGE      = 'diwamln/room-boking'
        DOCKER_CREDS      = 'docker-hub'
        
        // --- GIT & MANIFEST CONFIGURATION ---
        GIT_CREDS         = 'git-token'
        MANIFEST_REPO_URL = 'github.com/DevopsNaratel/deployment-manifests.git'
        MANIFEST_TEST_PATH = 'room-booking/dev/deployment.yaml'
        MANIFEST_PROD_PATH = 'room-booking/prod/deployment.yaml'
    }

    stages {
        stage('Checkout & Versioning') {
            steps {
                checkout scm
                script {
                    // Membuat Tag Unik: build-NUMBER-HASH
                    def commitHash = sh(returnStdout: true, script: "git rev-parse --short HEAD").trim()
                    env.BASE_TAG = "build-${BUILD_NUMBER}-${commitHash}"
                    currentBuild.displayName = "#${BUILD_NUMBER} (${env.BASE_TAG})"
                }
            }
        }

        stage('Build & Push Docker') {
            steps {
                container('docker') {
                    // Gunakan usernamePassword untuk kedua kredensial
                    withCredentials([
                        usernamePassword(
                            credentialsId: "${DOCKER_CREDS}", 
                            passwordVariable: 'DOCKER_PASSWORD', 
                            usernameVariable: 'DOCKER_USERNAME'
                        ),
                        usernamePassword(
                            credentialsId: "${env.GIT_ID}", // Gunakan ID 'git-token' Anda
                            passwordVariable: 'GITHUB_TOKEN', // Token diambil dari kolom password
                            usernameVariable: 'GIT_USER'
                        )
                    ]) {
                        sh """
                            docker login -u ${DOCKER_USERNAME} -p ${DOCKER_PASSWORD}
                            
                            # Jalankan build dengan network host jika DNS di k8s bermasalah
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

        stage('Update Manifest Dev') {
            steps {
                withCredentials([usernamePassword(credentialsId: "${GIT_CREDS}", passwordVariable: 'GIT_PASSWORD', usernameVariable: 'GIT_USERNAME')]) {
                    sh """
                        git config --global user.email "jenkins@naratel.net.id"
                        git config --global user.name "Jenkins CI/CD"
                        
                        # Cleanup and Clone
                        rm -rf temp_manifest
                        git clone https://${GIT_USERNAME}:${GIT_PASSWORD}@${MANIFEST_REPO_URL} temp_manifest
                        
                        cd temp_manifest
                        
                        # Update tag using sed (lebih robust dengan delimiter '|')
                        sed -i "s|image: ${DOCKER_IMAGE}:.*|image: ${DOCKER_IMAGE}:${env.BASE_TAG}|g" ${MANIFEST_TEST_PATH}
                        
                        # Commit and Push
                        git add ${MANIFEST_TEST_PATH}
                        if ! git diff-index --quiet HEAD; then
                            git commit -m "chore: update dev image to ${env.BASE_TAG}"
                            git push origin main
                        fi
                    """
                }
            }
        }

        stage('Promote to Prod (Approval)') {
            steps {
                input message: "Promote ke PROD?", ok: "Yes"
                withCredentials([usernamePassword(credentialsId: "${GIT_CREDS}", passwordVariable: 'GIT_PASSWORD', usernameVariable: 'GIT_USERNAME')]) {
                    sh """
                        cd temp_manifest
                        sed -i "s|image: ${DOCKER_IMAGE}:.*|image: ${DOCKER_IMAGE}:${env.BASE_TAG}|g" ${MANIFEST_PROD_PATH}
                        git add ${MANIFEST_PROD_PATH}
                        if ! git diff-index --quiet HEAD; then
                            git commit -m "chore: update prod image to ${env.BASE_TAG}"
                            git push origin main
                        fi
                    """
                }
            }
        }
    }
    
    post {
        always {
            cleanWs()
        }
    }
}
