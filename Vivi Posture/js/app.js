new Vue({
    el: '#app',
    data: {
        activeTab: 0,
        phone: '',
        inviteCode: '',
        banners: [
            'images/banner1.jpg',
            'images/banner2.jpg',
            'images/banner3.jpg'
        ],
        services: [
            { id: 1, name: '面部护理', icon: 'smile-o' },
            { id: 2, name: '身体塑形', icon: 'user-o' },
            { id: 3, name: '皮肤管理', icon: 'gem-o' },
            { id: 4, name: '更多服务', icon: 'apps-o' }
        ],
        packages: [
            {
                id: 1,
                title: '新人特惠',
                desc: '首次充值立享9折',
                price: '1000.00'
            },
            {
                id: 2,
                title: '年卡套餐',
                desc: '充3000送1000',
                price: '3000.00'
            }
        ]
    },
    methods: {
        onClickLeft() {
            history.back();
        },
        onClickRight() {
            // 打开菜单
        },
        onServiceClick(service) {
            // 跳转到服务详情页
        },
        onAppointment() {
            if (!this.phone) {
                this.$toast('请输入手机号');
                return;
            }
            // 提交预约
            axios.post('/api/appointment/book', {
                phone: this.phone,
                invite_code: this.inviteCode
            })
            .then(response => {
                if (response.data.code === 200) {
                    this.$toast.success('预约成功');
                } else {
                    this.$toast.fail(response.data.message);
                }
            })
            .catch(error => {
                this.$toast.fail('预约失败，请重试');
            });
        },
        onRecharge(package) {
            // 跳转到充值页面
        }
    }
}); 