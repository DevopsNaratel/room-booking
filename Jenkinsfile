pipeline {
  agent any

  environment {
    IMAGE_NAME = "rhan33/room-booking"
    IMAGE_TAG  = "staging-${BUILD_NUMBER}"
    FULL_IMAGE = "${IMAGE_NAME}:${IMAGE_TAG}"
  }

  stages {

    stage('Checkout') {
      steps {
        checkout scm
      }
    }

    // stage('Test (Laravel)') {
    //   steps {
    //     sh '''
    //       php -v || { echo "PHP not found"; exit 1; }
    //       composer install --no-scripts --no-interaction
    //       cp .env.example .env
    //       php artisan key:generate
    //       php artisan test
    //     '''
    //   }
    // }

    stage('Update Kubernetes Manifest (GitOps)') {
      steps {
        withCredentials([
          usernamePassword(
            credentialsId: 'github',
            usernameVariable: 'GIT_USER',
            passwordVariable: 'GIT_TOKEN'
          )
        ]) {
          sh '''
            sed -i "s|image: .*|image: ${FULL_IMAGE}|" \
              k8s/staging/deployment.yaml

            git config user.name "rharff"
            git config user.email "rharff@gmail.com"

            git add k8s/staging/deployment.yaml
            git commit -m "ci: deploy staging ${IMAGE_TAG}" || echo "No changes to commit"

            git remote set-url origin \
              https://${GIT_USER}:${GIT_TOKEN}@github.com/rharff/room-booking.git

            git push origin main
          '''
        }
      }
    }
  }

  post {
    success {
      echo "✅ Manifest updated → ArgoCD will deploy ${FULL_IMAGE}"
    }
    failure {
      echo "❌ Pipeline failed"
    }
  }
}
