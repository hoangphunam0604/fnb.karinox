# Fix JavaScript import.meta Error

## 🐛 **Lỗi:**

```
Error parsing JavaScript expression: import.meta may appear only with 'sourceType: "module"' (1:1)
```

## 🔧 **Nguyên nhân:**

- `import.meta` chỉ hoạt động trong ES modules
- Build tool chưa được cấu hình đúng cho ES modules
- Có thể là lỗi từ Vite/Webpack config

## ✅ **Giải pháp:**

### **1. Nếu dùng Vite (Electron + Vue):**

**vite.config.js:**

```javascript
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [vue()],
    base: './',
    build: {
        target: 'esnext', // Hoặc 'es2020'
        outDir: 'dist',
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'index.html'),
            },
        },
    },
    define: {
        // Fix import.meta cho production
        'import.meta.env.VITE_API_URL': JSON.stringify(process.env.VITE_API_URL || 'http://localhost/api'),
    },
});
```

### **2. Thay thế import.meta bằng process.env:**

**Thay vì:**

```javascript
const apiUrl = import.meta.env.VITE_API_URL;
```

**Dùng:**

```javascript
const apiUrl = process.env.VITE_API_URL || 'http://localhost/api';
```

### **3. Hoặc dùng config object:**

**config.js:**

```javascript
export const config = {
    apiUrl: process.env.NODE_ENV === 'production' ? 'https://your-domain.com/api' : 'http://localhost/api',
    wsUrl: process.env.NODE_ENV === 'production' ? 'wss://your-domain.com:6001' : 'ws://localhost:6001',
};
```

**Trong Vue component:**

```javascript
import { config } from '@/config';

export default {
    data() {
        return {
            apiUrl: config.apiUrl,
        };
    },
};
```

### **4. Electron Main Process:**

**main.js:**

```javascript
// Thay vì import.meta.url
const isDev = process.env.NODE_ENV === 'development';
const path = require('path');

const createWindow = () => {
    const win = new BrowserWindow({
        width: 1200,
        height: 800,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.js'),
        },
    });

    if (isDev) {
        win.loadURL('http://localhost:3000');
    } else {
        win.loadFile('dist/index.html');
    }
};
```

### **5. Package.json scripts:**

```json
{
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "electron": "electron .",
        "electron:dev": "concurrently \"npm run dev\" \"wait-on http://localhost:3000 && electron .\"",
        "build:electron": "npm run build && electron-builder"
    }
}
```

## 🎯 **Cho Print Management App:**

**ConnectionView.vue:**

```vue
<template>
    <div class="connection-view">
        <h2>Kết nối với chi nhánh</h2>
        <form @submit.prevent="connect">
            <input v-model="connectionCode" placeholder="Nhập mã kết nối 12 ký tự" maxlength="12" required />
            <button type="submit" :disabled="connecting">
                {{ connecting ? 'Đang kết nối...' : 'Kết nối' }}
            </button>
        </form>
    </div>
</template>

<script>
import { config } from '@/config';

export default {
    name: 'ConnectionView',
    data() {
        return {
            connectionCode: '',
            connecting: false,
            apiUrl: config.apiUrl,
        };
    },
    methods: {
        async connect() {
            this.connecting = true;
            try {
                const response = await fetch(`${this.apiUrl}/print/connect`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        connection_code: this.connectionCode,
                    }),
                });

                const result = await response.json();

                if (result.success) {
                    // Lưu config và chuyển trang
                    this.$store.commit('setConnectionData', result.data);
                    this.$router.push('/dashboard');
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Lỗi kết nối: ' + error.message);
            } finally {
                this.connecting = false;
            }
        },
    },
};
</script>
```

## 📝 **Mã kết nối hiện có:**

- **PIPPYKIDS001** - Gà Rán Pippy Kids
- **KARINOX00001** - Karinox Coffee
- **PLAYGROUND01** - Khu Vui Chơi Pippy Kids

Hãy thử một trong các giải pháp trên để fix lỗi import.meta!
