pipeline {
  agent {
    kubernetes {
      yaml """
apiVersion: v1
kind: Pod
spec:
  containers:
  - name: docker
    image: docker:27-cli
    command:
    - cat
    tty: true
    volumeMounts:
    - name: docker-sock
      mountPath: /var/run/docker.sock
  volumes:
  - name: docker-sock
    hostPath:
      path: /var/run/docker.sock
"""
    }
  }

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

    stage('Build & Push Image') {
      steps {
        container('docker') {
          withCredentials([usernamePassword(
            credentialsId: 'dockerhub',
            usernameVariable: 'DOCKER_USER',
            passwordVariable: 'DOCKER_PASS'
          )]) {
            sh '''
              echo "$DOCKER_PASS" | docker login -u "$DOCKER_USER" --password-stdin
              docker build -t $FULL_IMAGE .
              docker push $FULL_IMAGE
            '''
          }
        }
      }
    }

    stage('Update Kubernetes Manifest (GitOps)') {
      steps {
        withCredentials([usernamePassword(
          credentialsId: 'github',
          usernameVariable: 'GIT_USER',
          passwordVariable: 'GIT_TOKEN'
        )]) {
          sh '''
            sed -i "s|image: .*|image: $FULL_IMAGE|" k8s/staging/deployment.yaml

            git config user.name "rharff"
            git config user.email "rharff@gmail.com"

            git add k8s/staging/deployment.yaml
            git commit -m "ci: deploy staging $IMAGE_TAG" || echo "No changes"

            git remote set-url origin \
              https://$GIT_USER:$GIT_TOKEN@github.com/rharff/room-booking.git

            git push origin HEAD:main
          '''
        }
      }
    }
  }

  post {
    success {
      echo "✅ Image pushed & GitOps updated → ArgoCD will deploy $FULL_IMAGE"
    }
    failure {
      echo "❌ Pipeline failed"
    }
  }
}
