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

    stage('Test') {
      steps {
        sh '''
          docker run --rm \
            -v $(pwd):/app \
            -w /app \
            composer:2 bash -c "
              composer install --no-scripts --no-interaction
              cp .env.example .env
              php artisan key:generate
              php artisan test
            "
        '''
      }
    }

    stage('Build Docker Image') {
      steps {
        sh '''
          docker build \
            -t ${FULL_IMAGE} \
            -f Dockerfile \
            .
        '''
      }
    }

    stage('Push Image to DockerHub') {
      steps {
        withCredentials([
          usernamePassword(
            credentialsId: 'dockerhub',
            usernameVariable: 'DOCKER_USER',
            passwordVariable: 'DOCKER_PASS'
          )
        ]) {
          sh '''
            echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
            docker push ${FULL_IMAGE}
          '''
        }
      }
    }

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

            git config user.name "jenkins"
            git config user.email "jenkins@ci.local"

            git add k8s/staging/deployment.yaml
            git commit -m "ci: deploy staging ${IMAGE_TAG}" || echo "No changes to commit"

            git remote set-url origin \
              https://${GIT_USER}:${GIT_TOKEN}@github.com/ORG/REPO.git

            git push origin main
          '''
        }
      }
    }
  }

  post {
    success {
      echo "✅ Successfully built and deployed ${FULL_IMAGE}"
    }
    failure {
      echo "❌ Pipeline failed"
    }
    always {
      sh 'docker logout || true'
    }
  }
}
