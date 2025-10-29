# 快速启动指南

## 🚀 方式一：本地开发启动（推荐用于开发）

### 后端启动
```bash
cd backend
php artisan serve
```
访问：http://localhost:8000/api

### 前端启动
```bash
cd frontend
npm run dev
```
访问：http://localhost:5173

---

## 🐳 方式二：Docker Compose启动

```bash
# 构建并启动服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down
```

访问：
- 前端：http://localhost:5173
- 后端API：http://localhost:8000/api

---

## 📊 测试API

### 1. 创建小朋友
```bash
curl -X POST http://localhost:8000/api/children \
  -H "Content-Type: application/json" \
  -d '{
    "name": "小明",
    "birthday": "2018-05-20",
    "gender": "male"
  }'
```

### 2. 查看所有小朋友
```bash
curl http://localhost:8000/api/children
```

### 3. 给小朋友加星星
```bash
curl -X POST http://localhost:8000/api/children/1/stars/add \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 5,
    "reason": "认真做作业"
  }'
```

### 4. 创建奖品
```bash
curl -X POST http://localhost:8000/api/rewards \
  -H "Content-Type: application/json" \
  -d '{
    "name": "去游乐园",
    "star_cost": 100,
    "child_ids": [1]
  }'
```

### 5. 兑换奖品
```bash
curl -X POST http://localhost:8000/api/rewards/1/redeem \
  -H "Content-Type: application/json" \
  -d '{
    "deductions": [
      {"child_id": 1, "amount": 100}
    ]
  }'
```

---

## 🛠️ 开发工具

### 查看数据库
```bash
cd backend
sqlite3 database/database.sqlite

# 查看表
.tables

# 查看小朋友
SELECT * FROM children;

# 查看星星记录
SELECT * FROM star_records;

# 退出
.quit
```

### 清空数据库重新开始
```bash
cd backend
php artisan migrate:fresh
```

---

## ⚠️ 常见问题

### 1. 后端启动失败
```bash
# 检查PHP版本（需要8.2+）
php -v

# 重新安装依赖
cd backend
composer install
```

### 2. 前端启动失败
```bash
# 检查Node版本（需要20.19+）
node -v

# 重新安装依赖
cd frontend
rm -rf node_modules
npm install
```

### 3. storage权限问题
```bash
cd backend
chmod -R 775 storage bootstrap/cache
```

### 4. 图片上传失败
```bash
# 确保storage link存在
cd backend
php artisan storage:link
```

---

## 📱 下一步：前端开发

前端核心文件待创建：

1. **路由配置** - `frontend/src/router/index.ts`
2. **API封装** - `frontend/src/api/`
3. **页面组件** - `frontend/src/views/`
4. **通用组件** - `frontend/src/components/`
5. **动画效果** - `frontend/src/composables/useAnimations.ts`

详细开发指南请查看 `DEVELOPMENT.md`

---

**项目当前状态**: 后端完成 ✅ | 前端框架搭建完成 ✅ | 前端页面组件开发中 🚧
