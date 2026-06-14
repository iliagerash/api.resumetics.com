module.exports = {
  apps: [{
    name: 'resumetics-queue',
    script: 'php',
    args: 'artisan queue:work --queue=email-routing --sleep=3 --tries=3 --max-time=3600',
    cwd: '/home/webadmin/Resumetics/api.resumetics.com',
    autorestart: true,
    watch: false,
    max_restarts: 10,
    restart_delay: 5000,
  }]
}
