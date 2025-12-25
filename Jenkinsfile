pipeline {
    agent any

    environment {
        // Ubah bagian ini
        // sesuaikan dengan nama aplikasi anda begitu juga credentialnya
        APP_NAME        = 'room-booking' 
        DOCKER_IMAGE    = "diwamln/${APP_NAME}"
        REGISTRY_ID     = 'docker-hub'
        GIT_ID       = 'git-token'
        MANIFEST_REPO   = 'github.com/DevopsNaratel/deployment-manifests'
    }

    stages {
        stage('Setup') {
            steps {
                script {
                    def commitHash = sh(returnStdout: true, script: "git rev-parse --short HEAD").trim()
                    env.BASE_TAG = "build-${BUILD_NUMBER}-${commitHash}"
                    currentBuild.displayName = "#${BUILD_NUMBER}-${env.BASE_TAG}"
                }
            }
        }

        stage('Build & Push') {
            steps {
                script {
                    docker.withRegistry('', REGISTRY_ID) {
                        def appImage = docker.build("${DOCKER_IMAGE}:${env.BASE_TAG}")
                        appImage.push()
                        appImage.push('latest')
                    }
                }
            }
        }

        stage('Update Manifest') {
            steps {
                // Kita buat fungsi reusable untuk update manifest
                updateManifest('dev', "${APP_NAME}/dev/deployment.yaml")
            }
        }

        stage('Approval') {
            steps {
                input message: "Promote ke PROD?", ok: "Yes"
            }
        }

        stage('Promote to PROD') {
            steps {
                updateManifest('prod', "${APP_NAME}/prod/deployment.yaml")
            }
        }
    }
}

// Fungsi pembantu agar script utama tetap bersih
def updateManifest(envName, filePath) {
    echo "Updating ${envName} manifest..."
    sh "rm -rf temp_manifests_${envName}"
    dir("temp_manifests_${envName}") {
        withCredentials([usernamePassword(credentialsId: env.GIT_ID, usernameVariable: 'GIT_USER', passwordVariable: 'GIT_PASS')]) {
            sh "git clone https://${GIT_USER}:${GIT_PASS}@${env.MANIFEST_REPO} ."
            sh "sed -i -E 's|image: .*:.*|image: docker.io/${env.DOCKER_IMAGE}:${env.BASE_TAG}|g' ${filePath}"
            sh """
                git config user.email "jenkins@bot.com"
                git config user.name "Jenkins"
                git add .
                if ! git diff-index --quiet HEAD; then
                    git commit -m 'Deploy ${env.APP_NAME} to ${envName}: ${env.BASE_TAG}'
                    git push origin main
                fi
            """
        }
    }
}
