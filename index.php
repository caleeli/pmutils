<?php
if (file_exists(__DIR__ . '/.env')) {
    foreach (parse_ini_file(__DIR__ . '/.env') as $key=>$value) {
        $_ENV[$key] = $value;
    }
}

$pmHome = $_ENV['PROCESSMAKER_HOME'];
$composer = json_decode(file_get_contents($pmHome . '/composer.json'), true);

$packages = [
    'connector-docusign' => [ 'version' => $composer['require']['processmaker/connector-docusign'] ?? '' ],
    'connector-pdf-print' => [ 'version' => $composer['require']['processmaker/connector-pdf-print'] ?? '' ],
    'connector-send-email' => [ 'version' =>  $composer['require']['processmaker/connector-send-email'] ?? '' ],
    'nayra' => [ 'version' => $composer['require']['processmaker/nayra'] ?? '' ],
    'package-actions-by-email' => [ 'version' => $composer['require']['processmaker/package-actions-by-email'] ?? '' ],
    'package-auth' => [ 'version' => $composer['require']['processmaker/package-auth'] ?? '' ],
    'package-collections' => [ 'version' => $composer['require']['processmaker/package-collections'] ?? '' ],
    'package-data-sources' => [ 'version' => $composer['require']['processmaker/package-data-sources'] ?? '' ],
    'package-ellucian-ethos' => [ 'version' => $composer['require']['processmaker/package-ellucian-ethos'] ?? '' ],
    'package-savedsearch' => [ 'version' => $composer['require']['processmaker/package-savedsearch'] ?? '' ],
    'package-versions' => [ 'version' => $composer['require']['processmaker/package-versions'] ?? '' ],
    'packages' => [ 'version' => $composer['require']['processmaker/packages'] ?? '' ],
];

function packageVersion($package) {
    return '<button>develop</button> <input v-bind:value="packages[\'' . $package . '\'].version"><button @click="install(\'' . $package . '\', packages[\'' . $package . '\'].version)">ticket</button><br>';
}
?>
<html>

<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.0/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.12/vue.js"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-vue/2.19.0/bootstrap-vue.min.js"
        integrity="sha512-8kPdh/QOb9Lc2e3uQyfHWcqLq0WuyQ0awXGfKkXJw22ryOGhz6xuKnKdorhN1+2j1j6YGL0SnCU292v/zB10zg=="
        crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-vue/2.19.0/bootstrap-vue.min.css"
        integrity="sha512-rbXufjCYXcL7yXM6mQwoF2wO/swHjqYVQtysWe9i/hM2KAJpV1zRZ7UGb5EWPA93ols9hHGkOzuTytomkzfyzA=="
        crossorigin="anonymous" />
        <style>
        .package b {
            display: inline-block;
            width: 18em;
        }
    </style>
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
        <b-button @click="migrate()">
            PM4 MIGRATE SEED
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
        <b-button @click="clearRequests()">
            CLEAR REQUESTS
        </b-button>
        <b-button @click="openAdminer()">
            ADMINER
        </b-button>
        <b-button @click="openPM4()">
            PM4
        </b-button>
        <hr>
        <b-button @click="restart('pm4_mysql')">
            PM4 MYSQL
            <?php
                exec('service pm4_mysql status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
        <b-button @click="migrate('4.1')">
            PM4.1 MIGRATE SEED
        </b-button>
        <b-button @click="restart('pm4.1_horizon')">
            PM4.1 HORIZON
            <?php
                exec('service pm4.1_horizon status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
        <b-button @click="restart('pm4.1_echo')">
            PM4.1 ECHO
            <?php
                exec('service pm4.1_echo status', $o, $r);
                echo "status: " , $r ? $r : 'ACTIVE';
            ?>
        </b-button>
        <b-button @click="clearRequests()">
            CLEAR REQUESTS
        </b-button>
        <b-button @click="openAdminer()">
            ADMINER
        </b-button>
        <hr>
        <b>AT:</b> <?= $_ENV['PROCESSMAKER_HOME'] ?><br>
        <b>Install packages:</b><br>
            <div
                v-for="(package, packageName) in packages"
                class="package"
            >
                <b>{{ packageName }}</b>
                <input v-model="package.version"><button @click="install(packageName, package.version)">branch</button>
            </div>
        <hr>
        <pre>{{output}}</pre>
    </div>
    <script>
        new Vue({
            el: '#app',
            data() {
                return {
                    packages: <?= json_encode($packages) ?>,
                    output: "",
                };
            },
            methods: {
                restart(service) {
                    axios.get(`api.php/service/restart/${service}`).then(() => {
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    });
                },
                migrate(version='4.2') {
                    axios.get(`api.php/service/migrate/${version}`).then(() => {
                        setTimeout(() => {
                            window.location.reload();
                        }, 3000);
                    });
                },
                install(package, version) {
                    axios.get(`api.php/service/install?package=${encodeURIComponent(package)}&version=${encodeURIComponent(version)}`).then((response) => {
                        this.output = response.data.output;
                    });
                },
                clearRequests() {
                    axios.get(`api.php/service/clear_requests`);
                },
                openAdminer() {
                    window.open(
                        'http://localhost/adminer.php?server=127.0.0.1%3A3307&username=root&db=workflow',
                        'blank');
                },
                openPM4() {
                    window.open(
                        'http://localhost:8089',
                        'blank');
                }
            },
        });
    </script>
</body>

</html>