<?php
if (isset($_GET["shName"])) {
  $cmd = 'sh ./sh/'.$_GET["shName"].$_GET["args"];
  exec($cmd, $result);
  echo json_encode($result);
  exit;
}
if (isset($_GET["fileList"])) {
  exec("ls sh/*.sh", $result);
  $shList = [];
  foreach ($result as $key => $shName) {
    $file = file($shName);
    if (array_key_exists(1, $file)) {
      $exp = $file[1];
      if (!preg_match("/^#/", $exp)) {
        $exp = "#説明なし";
      }
    }
    $shList[$key] = array( "name"=>preg_replace("/^sh\//", "", $shName), "exp"=>$exp );
  }
  echo json_encode($shList);
  exit;
}
?>

<!DOCTYPE HTML>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <title>Dashboard</title>
  <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.min.css" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
</head>
<body>
  <div id="app">
    <v-app>
      <v-navigation-drawer
        v-model="drawer"
        fixed
        app
        clipped
        mobile-break-point="960"
      >
        <v-list class="pt-0">
          <v-list-tile @click="fileList">
            <v-list-tile-action>
              <v-icon>cached</v-icon>
            </v-list-tile-action>
            <v-list-tile-content>
              <v-list-tile-title>再読み込み</v-list-tile-title>
            </v-list-tile-content>
          </v-list-tile>
          <v-divider></v-divider>
          <template>
            <v-progress-linear class="my-0" height="2" :active="filesLoading" :indeterminate="filesLoading"></v-progress-linear>
          </template>

          <v-list-tile v-for="file in files" @click="selectFile(file)">
            <v-list-tile-content>
              <v-list-tile-title>{{ file.name }}</v-list-tile-title>
            </v-list-tile-content>
          </v-list-tile>

        </v-list>
      </v-navigation-drawer>
      <v-toolbar
        color="blue accent-2"
        dark
        fixed
        app
        clipped-left
      >
        <v-toolbar-side-icon @click="drawer=!drawer"></v-toolbar-side-icon>
        <v-toolbar-title>Dashboard</v-toolbar-title>
      </v-toolbar>

      <v-content>
        <v-container fluid fill-height>
          <v-layout>
            <v-flex xs12>
              <v-card>
                <v-card-title primary-title>
                  <div>
                    <div class="headline mb-2">{{ select.name }}</div>
                    <span>{{ select.exp }}</span>
                  </div>
                </v-card-title>
                <v-card-actions class="pa-3">
                  <v-combobox
                    v-model="args"
                    hide-selected
                    label="引数リスト"
                    hint="引数を順番に入力してください"
                    persistent-hint
                    multiple
                    small-chips
                    counter="true"
                    append-icon=""
                    item-text="name"
                    item-value="id"
                    :disabled="select.name=='シェルスクリプト名'"
                  >
                    <template v-slot:selection="{ item, parent, selected }">
                      <v-chip
                        v-if="item === Object(item)"
                        color="orange lighten-3"
                        :selected="selected"
                        label
                        small
                      >
                        <span class="pr-2">
                          {{ item.name }}
                        </span>
                        <v-icon
                          small
                          @click="parent.selectItem(item)"
                        >close</v-icon>
                      </v-chip>
                    </template>
                  </v-combobox>
                  <v-btn
                    class="ml-3"
                    @click="execCmd()"
                    color="success lighten-1"
                    :loading="loading"
                    :disabled="select.name=='シェルスクリプト名' || loading"
                  >
                    実行
                  </v-btn>
                </v-card-actions>
              </v-card>

              <v-card class="mt-3" :color="resultColor" style="min-height:120px;">
                <v-card-title class="pb-1" primary-title>
                  <div class="headline">実行結果</div>
                </v-card-title>
                <v-card-text class="pt-0 pb-4">
                  <kbd class='font-weight-bold mb-1' :class="result.cmd ? '' : 'd-none'">{{ result.cmd }}</kbd>
                  <span class="text-xs-right ml-2">{{ result.date }}</span>
                  <div style="white-space:pre-wrap; word-wrap:break-word;">{{ result.log }}</div>
                </v-card-text>
              </v-card>

              <v-card class="mt-5" color="grey lighten-2" :class="(histories[1] || (histories[0] && status)) ? '' : 'd-none'">
                <v-card-title class="py-2" primary-title>
                  <div class="headline">履歴</div>
                  <v-spacer></v-spacer>
                  <v-btn icon class="ma-0" @click="clearLocalStorage">
                    <v-icon>clear</v-icon>
                  </v-btn>
                </v-card-title>
              </v-card>

              <v-card class="mt-3" :color="(history.log==errorMessage ? 'error' : 'grey') + ' lighten-3'" v-for="(history, index) in histories" v-if="!(index==0 && !status)">
                <v-card-text class="pb-4">
                  <kbd class='font-weight-bold mb-1'>{{ history.cmd }}</kbd>
                  <span class="text-xs-right ml-2">{{ history.date }}</span>
                  <div style="white-space:pre-wrap;word-wrap:break-word;">{{ history.log }}</div>
                </v-card-text>
              </v-card>

            </v-flex>
          </v-layout>
        </v-container>
      </v-content>

    </v-app>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
  <script>
    new Vue({
      el: "#app",
      data: {
        drawer: window.innerWidth >= 960 ? true : false,
        status: false,
        files: [],
        select: { name: "シェルスクリプト名", exp: "#説明" },
        nonce: 0,
        args: [],
        errorMessage: "エラーが発生しました",
        filesLoading: false,
        loading: false,
        result: { cmd: "", log: "", date: "" },
        histories: [],
        resultColor: ""
      },
      watch: {
        args(val, prev) {
          if (val.length === prev.length) return
          this.args = val.map(v => {
            if (typeof v === 'string') {
              v = {
                name: v,
                id: this.nonce
              }
              this.nonce++
            }
            return v
          })
        }
      },
      mounted: function() {
        axios
        .get("/shell", {
          params: {
            fileList: true,
          }
        })
        .then(res => {
          this.files = res.data;
        })
        .catch(() => {
          this.files.name = this.errorMessage;
        });
        if (localStorage.getItem("history")) {
          this.histories = JSON.parse(localStorage.getItem("history"));
          this.status = true;
        }
      },
      methods: {
        fileList: function(){
          this.filesLoading = true;
          axios
          .get("/shell", {
            params: {
              fileList: true,
            }
          })
          .then(res => {
            console.log(res.data);
            this.files = res.data;
          })
          .catch(() => {
            this.files.name = this.errorMessage;
          })
          .then(() => {
            setTimeout(() => { this.filesLoading = false }, 1000);
          });
        },
        selectFile: function(file) {
          this.select.name = file.name;
          this.select.exp = file.exp;
          if (window.innerWidth < 960) {
            this.drawer = !this.drawer;
          }
        },
        execCmd: function() {
          let argsList = "";
          for (var i = 0; i < this.args.length; i++) {
            argsList += " "+this.args[i].name;
          }
          this.status = true;
          this.loading = true;
          this.result.cmd = "$ ./" + this.select.name + argsList;
          this.result.log = "";
          this.resultColor = "";
          axios
          .get("/shell", {
            params: {
              shName: this.select.name,
              args: argsList
            }
          })
          .then(res => {
            let data = res.data;
            this.result.date = new Date().toLocaleString();
            if (!data[0]) {
              throw new Error();
            }
            for (let i = 0; i < data.length-1; i++) {
              this.result.log += data[i] + "\n";
            }
            this.result.log += data.slice(-1)[0];
            this.loading = false;
            this.resultColor = "success lighten-5";
          })
          .catch(() => {
            this.result.log = this.errorMessage;
            this.loading = false;
            this.resultColor = "error lighten-3";
          })
          .then(() => {
            this.status = false;
            this.histories.unshift({ cmd: this.result.cmd, log: this.result.log, date: this.result.date });
            localStorage.setItem("history", JSON.stringify(this.histories));
          });
        },
        clearLocalStorage: function() {
          if(confirm("履歴を削除しますか？")) {
            localStorage.removeItem("history");
            this.result = {};
            this.histories = [];
            this.resultColor = "";
          }
        }
      }
    })
  </script>
</body>
</html>
