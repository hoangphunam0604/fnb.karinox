module.exports = {
  apps: [
    {
      name: 'karinox-reverb',
      script: 'artisan',
      args: 'reverb:start',
      cwd: '/var/www/karinox-fnb', // Thay đổi path phù hợp với server
      interpreter: 'php',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        APP_ENV: 'production',
        NODE_ENV: 'production'
      },
      env_production: {
        APP_ENV: 'production',
        NODE_ENV: 'production'
      },
      log_file: '/var/log/pm2/karinox-reverb.log',
      out_file: '/var/log/pm2/karinox-reverb-out.log',
      error_file: '/var/log/pm2/karinox-reverb-error.log',
      time: true,
      // Restart delay
      restart_delay: 4000,
      // Max restarts trong 1 phút
      max_restarts: 10,
      min_uptime: '10s'
    },

    {
      name: 'karinox-queue',
      script: 'artisan',
      args: 'queue:work --sleep=3 --tries=3 --max-time=3600',
      cwd: '/var/www/karinox-fnb',
      interpreter: 'php',
      instances: 2, // Chạy 2 worker
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      env: {
        APP_ENV: 'production'
      },
      log_file: '/var/log/pm2/karinox-queue.log',
      out_file: '/var/log/pm2/karinox-queue-out.log',
      error_file: '/var/log/pm2/karinox-queue-error.log',
      time: true,
      restart_delay: 1000,
      max_restarts: 15,
      min_uptime: '5s'
    }
  ]
};