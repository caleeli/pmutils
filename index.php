<html>

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.0/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.12/vue.min.js" integrity="sha512-BKbSR+cfyxLdMAsE0naLReFSLg8/pjbgfxHh/k/kUC82Hy7r6HtR5hLhobaln2gcTvzkyyehrdREdjpsQwy2Jw==" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-vue/2.19.0/bootstrap-vue.min.js" integrity="sha512-8kPdh/QOb9Lc2e3uQyfHWcqLq0WuyQ0awXGfKkXJw22ryOGhz6xuKnKdorhN1+2j1j6YGL0SnCU292v/zB10zg==" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-vue/2.19.0/bootstrap-vue.min.css" integrity="sha512-rbXufjCYXcL7yXM6mQwoF2wO/swHjqYVQtysWe9i/hM2KAJpV1zRZ7UGb5EWPA93ols9hHGkOzuTytomkzfyzA==" crossorigin="anonymous" />
</head>

<body>
    <div id="app">
    <b-button @click="restart('pm4_mysql')">
            PM4 MYSQL
            <?php
                exec('service pm4_mysql status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
        <b-button @click="restart('pm4_horizon')">
            PM4 HORIZON
            <?php
                exec('service pm4_horizon status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
        <b-button @click="restart('pm4_echo')">
            PM4 ECHO
            <?php
                exec('service pm4_echo status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
    </div>
    <script>
    new Vue({
        el: '#app',
        methods: {
            restart(service) {
                axios.get(`api.php/service/restart/${service}`).then(() => {
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                });
            },
        },
    });
    </script>
</body>

</html>
