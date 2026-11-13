<template lang="">
    <div v-if="isCMS" class="d-flex main_app">
        <loaderVue v-if="loading" :loadingText="loaderText"/>
        <div
            @click.self="closeMobileNav"
            class="left-sec p-3 animate__animated animate__fadeInLeft"
            :class="{collapsedNav : collapsedNav, smallScreenNav:smallScreenNav, hiddenSideNav: hiddenSideNav, mobileNavOpen: smallScreenNav && !hiddenSideNav}"
            v-if="BarsVisibilty">
            <NavBar
                :logInProps="logInItems"
                :isMobileNav="smallScreenNav"
                @closeMobileNav="closeMobileNav"
            />
        </div>
        <div class="right-sec" :class="{full : !BarsVisibilty, collapsedNav : collapsedNav,smallScreenNav:smallScreenNav}">
            <div class="top-bar" v-if="BarsVisibilty">
                <TopBar
                    :logInProps="logInItems"
                    :showHamburger="smallScreenNav"
                    :mobileNavOpen="smallScreenNav && !hiddenSideNav"
                    @toggleMobileNav="toggleSideNav"
                />
            </div>
            <div class="main-content container-fluid mb-3">
                <router-view
                    @hideBarsEvent="hideBars"
                    @showBarsEvent="showBars"
                    @adminCheckEvent="checkAdmin"
                    @checkWindowEvent="checkWindow"
                    @getConvosEvent="getAllConvos"
                    @activeConvoEvent="setActiveConvo"
                    @getMessagesEvent="firebaseMsgSend"
                    @activeGroupEvent="setActiveGroup"
                    @getGroupsEvent="getAllGroups"
                    @getGroupMessagesEvent="firebaseGroupMsgSend"
                    :chatProps="chatItems"
                    :groupProps="groupItems"
                    :logInProps="logInItems">
                </router-view>
            </div>
        </div>
    </div>
    <div v-else class="main_app">
        <div>
            <router-view></router-view>
        </div>
    </div>
</template>
<script>
import NavBar from './components/navbar.vue';
import TopBar from './components/topbar.vue';
import axios from 'axios';
import config from './config';
import loaderVue from './components/loader.vue';
import firebase from 'firebase/compat/app';
import 'firebase/compat/firestore';

export default {
    components: { NavBar, TopBar, loaderVue },
    mounted() {
        let text = window.location.pathname;
        let result = text.includes("cms");
        if (result) {
            this.isCMS = true;
        }
    },
    data() {
        return {
            BarsVisibilty: true,
            collapsedNav: false,
            smallScreenNav: false,
            hiddenSideNav: true,
            adminLoggedin: false,
            isCMS: false,
            loading: false,
            loaderText: '',
            chatItems: {
                convos: [],
                messages: [],
                active: null,
                chatListLoader: false,
                chatMsgsLoader: false,
            },
            groupItems: {
                groups: [],
                messages: [],
                active: null,
                groupListLoader: false,
                groupMsgsLoader: false,
            },
            logInItems: {
                name: null,
                role: null,
                image: null,
                id: null
            },
            fireDB: firebase.firestore()
        }
    },
    created() {
        this.checkWindow();
        window.addEventListener("resize", this.checkWindow);
    },
    watch: {
        $route() {
            this.closeMobileNav();
        }
    },
    methods: {
        getAllConvos(m) {
            if (m == 1) {
                this.loading = true;
                this.loaderText = 'Getting chat list';
                this.chatItems.convos = [];
            }
            const apiConfig = {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            };
            axios.get(config.baseApiUrl + 'all-chats', apiConfig).then(res => {
                this.loading = false;
                if (res.data.status) {
                    this.chatItems.convos = res.data.data;
                    if (this.chatItems.active !== null)
                        this.getChatMesssages();
                }
                else
                    alert("Error fetching conversations");
            }).catch(er => {
                this.loading = false;
                alert("Error fetching conversations: " + er.message);
            });
        },
        getChatMesssages(m) {
            if (m == 1) {
                this.loading = true;
                this.loaderText = 'Getting chats';
                this.chatItems.messages = [];
            }
            const apiConfig = {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            };
            axios.get(config.baseApiUrl + 'chat-messages/' + this.chatItems.active.id, apiConfig).then(res => {
                this.loading = false;
                if (res.data.status) {
                    this.chatItems.messages = res.data.data.reverse();
                    this.$router.currentRoute.value.matched[0].components.default.methods.updateScroll();
                }
                else
                    alert("Error fetching messages");
            }).catch(er => {
                this.loading = false;
                alert("Error fetching conversations: " + er.message);
            });
        },
        setActiveConvo(convo) {
            this.chatItems.active = convo;
            this.getChatMesssages(1);
            // this.getAllConvos();
        },
        async firebaseMsgSend(msg) {
            let postData = {
                "chat_id": this.chatItems.active.id,
                "msg": msg,
                "sendBy": "admin"
            }
            // this.getChatMesssages();
            await this.fireDB.collection('single-chats').add(postData);
        },
        newMessageListener() {
            this.fireDB.collection('single-chats').onSnapshot(querySnap => {
                let numMsgs = querySnap.docs.map(doc => doc.data());
                if (numMsgs.length !== 0) {
                    if (this.$route.fullPath === '/cms/messages') {
                        this.getAllConvos();
                        if (this.chatItems.active !== null)
                            this.getChatMesssages();
                    } else {
                        const audio = new Audio('/assets/notification_sound.mp3');
                        audio.play().catch(() => {});
                    }
                }
                this.deleteMessageFirebase();
            });
        },
        deleteMessageFirebase() {
            this.fireDB.collection('single-chats').get().then(res => {
                res.forEach(element => {
                    if (element.data().sendBy === 'user') {
                        element.ref.delete();
                    }
                });
            });
        },
        getAllGroups(m) {
            if (m == 1) {
                this.loading = true;
                this.loaderText = 'Getting all groups';
                this.groupItems.groups = [];
            }
            const apiConfig = {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            };
            axios.get(config.baseApiUrl + 'my-groups', apiConfig).then(res => {
                this.loading = false;
                this.groupItems.groups = res.data.data;
                if (this.groupItems.active !== null)
                    this.getGroupMesssages();
            }).catch(er => {
                this.loading = false;
                alert("Error fetching groups: " + er.message);
            });
        },
        getGroupMesssages(m) {
            if (m == 1) {
                this.loading = true;
                this.loaderText = 'Getting group chat';
                this.groupItems.messages = [];
            }
            const apiConfig = {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            };
            axios.get(config.baseApiUrl + 'group-messages/' + this.groupItems.active.id, apiConfig).then(res => {
                this.loading = false;
                this.groupItems.messages = res.data.data.reverse();
                this.$router.currentRoute.value.matched[0].components.default.methods.updateScroll();
            }).catch(er => {
                this.loading = false;
                alert("Error fetching group messages: " + er.message);
            });
        },
        setActiveGroup(group, m) {
            this.groupItems.active = group;
            if (this.groupItems.active.members > 0) {
                this.getGroupMesssages(m);
            }
            // this.getAllGroups();
        },
        async firebaseGroupMsgSend(msg) {
            let postData = {
                "group_id": this.groupItems.active.id,
                "msg": msg
            }
            await this.fireDB.collection('group-chats').add(postData);
        },
        newGroupMessageListener() {
            this.fireDB.collection('group-chats').onSnapshot(querySnap => {
                let numMsgs = querySnap.docs.map(doc => doc.data());
                if (numMsgs.length !== 0) {
                    if (this.$route.fullPath === '/cms/groups') {
                        this.getAllGroups();
                        if (this.groupItems.active !== null)
                            this.getGroupMesssages();
                    } else {
                        const audio = new Audio('/assets/notification_sound.mp3');
                        audio.play().catch(() => {});
                    }
                    this.deleteGroupMessageFirebase();
                }
            });
        },
        deleteGroupMessageFirebase() {
            this.fireDB.collection('group-chats').get().then(res => {
                res.forEach(element => {
                    element.ref.delete();
                });
            });
        },
        checkWindow() {
            if (this.BarsVisibilty) {
                let size = window.innerWidth;
                if (size < 992) {
                    this.collapsedNav = false;
                    this.smallScreenNav = true;
                }
                else {
                    this.smallScreenNav = false;
                    this.hiddenSideNav = true;
                }
            }
        },
        toggleSideNav() {
            this.hiddenSideNav = !this.hiddenSideNav;
            if (this.smallScreenNav && !this.hiddenSideNav) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },
        closeMobileNav() {
            if (this.smallScreenNav) {
                this.hiddenSideNav = true;
                document.body.style.overflow = '';
            }
        },
        collapseToggle() {
            this.collapsedNav = !this.collapsedNav;
        },
        hideBars() {
            this.smallScreenNav = false
            this.collapsedNav = false;
            this.BarsVisibilty = false;
            document.body.style.overflow = '';
        },
        showBars() {
            this.BarsVisibilty = true;
            this.checkWindow();
        },
        checkAdmin() {
            const token = config.storage.getItem('fwd_session_token');
            if (token != null) {
                axios.get(config.baseApiUrl + 'isAuth', {
                    headers: {
                        Authorization: 'Bearer ' + token
                    }
                }).then(res => {
                    if (res.data.status == false) {
                        alert("You Don't Have Previllages To Access This Page");
                        config.storage.removeItem('fwd_session_token');
                        this.$router.push('/cms/login');
                    } else {
                        this.logInItems.name = res.data.data.name;
                        this.logInItems.role = res.data.data.role;
                        this.logInItems.id = res.data.data.id;
                        this.logInItems.image = res.data.data.image;
                        if (this.adminLoggedin === false) {
                            this.adminLoggedin = true;
                            this.newMessageListener();
                            this.newGroupMessageListener();
                        }
                    }
                }).catch(er => {
                    if (er.response.status == 401) {
                        alert("You have been Logged out, Please login again");
                        config.storage.removeItem('fwd_session_token');
                        this.$router.push('/cms/login');
                    }
                    else {
                        console.log("Error: ", er);
                        alert("Something went wrong")
                    }
                });
            }
            else {
                this.$router.push('/cms/login');
            }
        }
    },
}
</script>
<style>
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

.main_app {
    max-width: 100vw;
    overflow-x: hidden;
}

.main-content {
    margin-top: 110px !important;
    max-width: 100%;
    overflow-x: hidden;
}

/* Keep wide tables usable without breaking the page shell */
.main-content .table-responsive,
.main-content .for_data_table,
.main-content .vue3-easy-data-table__main {
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media screen and (max-width:991.98px) {
    .main-content {
        margin-top: 125px !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }
}

@media screen and (max-width:576px) {
    .main-content {
        margin-top: 120px !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }
}

.main_app .menu_item {
    padding: 15px;
}

.main_app .menu_item:hover {
    background-color: #c5c5c5;
}

.main_app .menu_item a {
    color: black;
}

.main_app .top-bar {
    position: fixed;
    width: inherit;
    z-index: 55;
}

/*--------------------------default view----------------------- */

.main_app .left-sec {
    width: 280px;
    height: 100vh;
    position: fixed;
    left: 0;
    right: 0;
}

.main_app .right-sec {
    width: calc(100% - 280px);
    margin-left: 280px;
}

/* ---------------------view when navbar is collapsed -----------------*/

.main_app .left-sec.collapsedNav {
    width: 100px !important;
}

.main_app .right-sec.collapsedNav {
    width: calc(100% - 100px) !important;
    margin-left: 100px !important;
}

/*----------------full screeen view when navbar and top bar is hidden--------------- */

.main_app .right-sec.full {
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

.main_app .right-sec.full>.main-content {
    margin-top: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
}

/*---------------------------hidden nav for small screens */

.main_app .right-sec.smallScreenNav {
    width: 100% !important;
    margin: 0 !important;
}

.main_app .right-sec.smallScreenNav .top-bar {
    width: 100%;
    left: 0;
}

/*-----------------------------setting fixed position navbar----------------- */

.main_app .left-sec.smallScreenNav {
    position: fixed;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.55);
    z-index: 120;
    padding: 0 !important;
    top: 0;
    left: 0;
}

.main_app .left-sec.smallScreenNav > * {
    width: min(280px, 85vw);
    height: 100%;
    border-radius: 0 1rem 1rem 0 !important;
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.2);
}

.main_app .left-sec.smallScreenNav .menu-coll-btn {
    display: none !important;
}

.main_app .left-sec.smallScreenNav.hiddenSideNav {
    display: none;
}

.main_app .left-sec.mobileNavOpen {
    display: block;
}
</style>
