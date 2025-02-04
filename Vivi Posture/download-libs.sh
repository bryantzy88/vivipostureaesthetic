#!/bin/bash

# 创建目录
mkdir -p js css images/services admin/css

# 下载 JavaScript 库文件
echo "下载 Vue.js..."
curl -L https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.min.js -o js/vue.min.js

echo "下载 Vant UI JS..."
curl -L https://cdn.jsdelivr.net/npm/vant@2.12/lib/vant.min.js -o js/vant.min.js

echo "下载 Axios..."
curl -L https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js -o js/axios.min.js

echo "下载 ECharts..."
curl -L https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js -o js/echarts.min.js

# 下载 CSS 文件
echo "下载 Vant UI CSS..."
curl -L https://cdn.jsdelivr.net/npm/vant@2.12/lib/index.css -o css/vant.css

# 下载示例图片
echo "下载示例图片..."
curl -L https://source.unsplash.com/800x600/?beauty -o images/hero.jpg
curl -L https://source.unsplash.com/800x600/?spa -o images/appointment.jpg
curl -L https://source.unsplash.com/800x600/?massage -o images/background.jpg
curl -L https://source.unsplash.com/800x600/?facial -o images/case1.jpg
curl -L https://source.unsplash.com/800x600/?logo -o images/logo.png

# 下载服务图片
for i in {1..6}
do
  curl -L https://source.unsplash.com/600x400/?beauty,spa -o images/services/service$i.jpg
done

echo "所有文件下载完成！" 