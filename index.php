<?php
exec("ls sh", $shList);;

if (isset($_POST["shName"])) {
  if (isset($_POST["args"]) && $_POST["args"] != "[]") {
    $argsList = $_POST["args"];
  }
  exec("./sh/".$_POST["shName"].$argsList, $result);
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
          <v-list-tile @click="" onclick="location.reload()">
            <v-list-tile-action>
              <v-icon>cached</v-icon>
            </v-list-tile-action>
            <v-list-tile-content>
              <v-list-tile-title>再読み込み</v-list-tile-title>
            </v-list-tile-content>
          </v-list-tile>
          <v-divider></v-divider>
          <?php
          foreach($shList as $shName){
            echo "
            <v-list-tile @click='select.name=`$shName`'>
              <v-list-tile-content>
                <v-list-tile-title>$shName</v-list-tile-title>
              </v-list-tile-content>
            </v-list-tile>";
          }
          ?>
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
                    {{ select.exp }}
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
                    item-text="text"
                    item-value="id"
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
                          {{ item.text }}
                        </span>
                        <v-icon
                          small
                          @click="parent.selectItem(item)"
                        >close</v-icon>
                      </v-chip>
                    </template>
                  </v-combobox>
                  <v-form method="post">
                    <input type="hidden" name="shName" :value="select.name" required>
                    <input type="hidden" name="args" :value="makeArgsList">
                    <v-btn type="submit" class="ml-3" color="success lighten-1" :disabled="select.name=='シェルスクリプト名'">実行</v-btn>
                  </v-form>
                </v-card-actions>
              </v-card>

              <v-card class="mt-3" color="grey lighten-3" style="min-height:120px;">
                <v-card-title class="pb-4" primary-title>
                  <v-layout>
                    <v-flex>
                      <div class="headline mb-2">実行結果</div>
                      <?php
                      if (isset($result)) {
                        echo "<div class='font-weight-bold mb-1'>$ ./".$_POST["shName"].$argsList."</div><div>";
                        foreach ($result as $line) {
                          if ($line === end($result)) {
                            echo $line."</div>";
                            break;
                          }
                          echo $line."<br>";
                        }
                      }
                      ?>
                    </v-flex>
                  </v-layout>
                </v-card-title>
              </v-card>
            </v-flex>
          </v-layout>
        </v-container>
      </v-content>

    </v-app>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/vuetify/dist/vuetify.js"></script>
  <script>
    new Vue({
      el: "#app",
      data: {
        drawer: window.innerWidth >= 960 ? true : false,
        select: { name: "シェルスクリプト名", exp: "説明" },
        nonce: 0,
        args: []
      },
      watch: {
        args(val, prev) {
          if (val.length === prev.length) return
          this.args = val.map(v => {
            if (typeof v === 'string') {
              v = {
                text: v,
                id: this.nonce
              }
              this.nonce++
            }
            return v
          })
        }
      },
      computed: {
        makeArgsList: function() {
          let argsList = "";
          for (var i = 0; i < this.args.length; i++) {
            argsList += " "+this.args[i].text;
          }
          return argsList;
        }
      }
    })
  </script>
</body>
</html>
