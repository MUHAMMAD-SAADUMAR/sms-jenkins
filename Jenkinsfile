pipeline {
    agent any

    triggers {
        githubPush()
    }

    options {
        skipDefaultCheckout()
        timeout(time: 10, unit: 'MINUTES')
    }

    environment {
        DB_ROOT_PASSWORD = credentials('sms2-db-root-pass')
        DB_PASSWORD      = credentials('sms2-db-pass')
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Lint PHP') {
            agent {
                docker {
                    image 'php:8.3-cli'
                    reuseNode true
                }
            }
            steps {
                sh 'find app -name "*.php" -print0 | xargs -0 -n1 php -l'
            }
        }

        stage('Deploy') {
            steps {
                sh 'docker compose -f docker-compose-part2.yml down || true'
                sh 'docker compose -f docker-compose-part2.yml up -d'
            }
        }

        stage('Health check') {
            steps {
                sh '''
                    for i in $(seq 1 30); do
                        if curl -fs http://localhost:8086/health.php | grep -q '"status" *: *"ok"'; then
                            echo "App is UP on port 8086"
                            exit 0
                        fi
                        sleep 5
                    done
                    echo "App did not become healthy"
                    docker compose -f docker-compose-part2.yml logs --tail=50
                    exit 1
                '''
            }
        }
    }

    post {
        always {
            script {
                def committer = sh(script: "git log -1 --pretty=format:'%ae'", returnStdout: true).trim()
                def author    = sh(script: "git log -1 --pretty=format:'%an'", returnStdout: true).trim()
                emailext(
                    to: committer,
                    subject: "${currentBuild.currentResult}: ${env.JOB_NAME} #${env.BUILD_NUMBER}",
                    mimeType: 'text/html',
                    body: """<p>Hello ${author},</p>
                             <p>Your push triggered build <b>#${env.BUILD_NUMBER}</b> of <b>${env.JOB_NAME}</b>.</p>
                             <p>Result: <b>${currentBuild.currentResult}</b></p>
                             <p>App: <a href="http://52.220.47.207:8086">http://52.220.47.207:8086</a></p>
                             <p>Console log: <a href="${env.BUILD_URL}console">${env.BUILD_URL}console</a></p>"""
                )
            }
        }
    }
}
