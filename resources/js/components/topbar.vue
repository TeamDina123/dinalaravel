<template lang="">
    <div class="topbar-wrap">
        <div class="top_bar w-100">
            <div class="d-flex bar align-items-center">
                <button
                    v-if="showHamburger"
                    type="button"
                    class="hamburger-btn"
                    :class="{ open: mobileNavOpen }"
                    aria-label="Toggle navigation menu"
                    @click="$emit('toggleMobileNav')"
                >
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="search-wrap flex-grow-1">
                    <div class="position-relative w-100">
                        <input @focusin="clientsDiv=true" @focusout="clientsDiv=false" type="text" v-model="search" placeholder="Find a Client" class="client-search-input">
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon" class="img-fluid position-absolute search-icon">
                        <div v-show="clientsDiv" class="position-absolute brds-3 shd_card p-0 client-search-results">
                            <div class="w-100 d-flex justify-content-between client px-3 py-1" style="min-height:30px;" @mousedown="openClientProfile(items.id)" v-for="(items, index) in filteredClient" :key="index">
                                <p class="mb-0 text-truncate pe-2">{{items.full_name}}</p>
                                <p class="mb-0 flex-shrink-0">{{items.subscription}}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-center topbar-actions" style="position: relative;">
                    <div class="d-flex border-end border-secondary">
                        <div class="bg-white rounded-circle border me-2 position-relative notify-btn">
                            <span v-if="unread_count!==0&&unread_count!==null" class="position-absolute prim_bg rounded-circle unread-badge">{{unread_count}}</span>
                            <img @click="toggleNotify" src="/cms-assets/images/navbar-topbar/NotificationBell.png" alt="noti" class="img-fluid" style="cursor: pointer;">
                        </div>
                    </div>
                    <div v-if="Notificationpop">

                        <div class="notify">
                            <svg
                            @click="CloseNotify"
                            style="
                            position: absolute;
                            top: 6px;
                            right: 18px;
                            width: 12px;
                            cursor: pointer;
                            "
                            xmlns="http://www.w3.org/2000/svg"
                            width="19.828"
                            height="19.828"
                            viewBox="0 0 19.828 19.828"
                        >
                            <g
                                id="Group_55866"
                                data-name="Group 55866"
                                transform="translate(-952.586 -160.586)"
                            >
                                <line
                                    id="Line_10"
                                    data-name="Line 10"
                                    x1="17"
                                    y2="17"
                                    transform="translate(954 162)"
                                    fill="none"
                                    stroke="#707070"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                                <line
                                    id="Line_11"
                                    data-name="Line 11"
                                    x2="17"
                                    y2="17"
                                    transform="translate(954 162)"
                                    fill="none"
                                    stroke="#707070"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </g>
                        </svg>
                            <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #707070; margin-top: 20px;">
                                <p style="font-size: 8px; font-weight: bold; margin-bottom: 10px;">View</p>
                                <p @click="markAllRead" style="font-size: 8px; font-weight: bold; margin-bottom: 10px;cursor:pointer;">Mark all read</p>
                            </div>
                            <div style="overflow: auto;">
                                <div v-for="item of NotificationArray" :key="item.id || item.title" :class="{'unread': item.status==0}" style="display: flex; margin-top: 15px; border: 1px solid #EDEDED; padding: 5px; border-radius: 8px;">
                                   <div class="col-9" style="display: flex;">
                                       <img :src="item.userImage" alt="" class="img-fluid rounded-circle" style="height:25px;width:25px; margin-right: 5px;">
                                       <div>
                                           <p style="margin-bottom: 0px;font-weight: bold;" class="h8">{{item.title}}</p>
                                           <p style="width: 75%;" class="h8">{{item.content}}</p>
                                       </div>
                                   </div>
                                   <div class="col-3 d-flex flex-column">
                                       <span class="h8">{{item.timeDiff}}</span>
                                   </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex position-relative justify-content-center align-items-center">
                        <div class="prof-img">
                            <img :src="logInProps.image" alt="profile">
                        </div>
                        <div class="dropdown profile-dropdown">
                            <div class="prof-name" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false" >
                                <h3 class="d-flex align-items-center mb-0 profile-name-heading">
                                    <strong class="profile-name-text">{{logInProps.name}}</strong>
                                    <i class="fa-solid fa-angle-down mt-2" style="font-size: 15px;margin-left: 10px;padding-top: 2px;"></i>
                                </h3>
                                <p class="mb-0 profile-role">{{logInProps.role}}</p>
                            </div>
                            <ul class="tsl dropdown-menu border-0" aria-labelledby="dropdownMenuButton1">
                                <li class="py-2"><button class="dropdown-item" style="text-align: center;" @click="this.$router.push('/cms/profile')"><img src="/cms-assets/images/G55730.png" alt="" class="img-fluid me-3" style="max-height:25px">Profile</button></li>
                                <li class="py-2"><button class="dropdown-item" style="text-align: center;" @click="logout"><img src="/cms-assets/images/G55730.png" alt="" class="img-fluid me-3" style="max-height:25px">Logout</button></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <clientPopup v-if="clientPopupComponent" :idForDetails="clientId" :logInDetails="logInProps" @close="clientPopupComponent = false"/>
    </div>
<div v-if="Notificationpop" @click="Notificationpop=false" class="position-absolute" style="height:100vh;width:100vw;top:0;right:0;"></div>
</template>
<script>
import axios from 'axios';
import config from '../config';
import clientPopup from '../components/clients/clientPopup.vue'
export default {
    components: { clientPopup },
    props: {
        logInProps: { type: Object, default: () => ({}) },
        showHamburger: { type: Boolean, default: false },
        mobileNavOpen: { type: Boolean, default: false },
    },
    emits: ['toggleMobileNav'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            profMenu: false,
            Notificationpop: false,
            NotificationArray: [],
            unread_count: null,
            clientsDiv: false,
            clientPopupComponent: false,
            clientId: null,
            users: [],
            search: '',
        }
    },
    computed: {
        filteredClient() {
            return this.users.filter((items) => {
                return items.full_name.toLowerCase().includes(this.search.toLowerCase());
            });
        }
    },
    mounted() {
        this.getAdminNotification();
        this.getClients();
        this.timer();
    },
    methods: {
        markAllRead() {
            console.log(this.NotificationArray);
            for (let index = 0; index < this.NotificationArray.length; index++) {
                this.NotificationArray[index].status = 1;
            }
        },
        getAdminNotification() {
            const token = config.storage.getItem('fwd_session_token');
            if (!token) return;
            axios.get(config.baseApiUrl + 'admin-notifications', this.apiConfig).then(res => {
                this.NotificationArray = res.data.data.notifications;
                this.unread_count = res.data.data.unread_count;
            }).catch(res => {

            });
        },
        timer() {
            setTimeout(() => {
                this.getAdminNotification();
                this.timer();
            }, 60000);
        },
        openClientProfile(m) {
            this.clientId = m;
            this.ClientPopup();
        },
        ClientPopup() {
            this.clientPopupComponent = !this.clientPopupComponent;
        },
        toggleNotify() {
            this.unread_count = 0;
            this.Notificationpop = !this.Notificationpop;
        },
        CloseNotify() {
            this.Notificationpop = false;
        },
        toggleProfMenu() {
            this.profMenu = !this.profMenu;
        },
        logout() {
            const token = config.storage.getItem('fwd_session_token');
            config.storage.removeItem('fwd_session_token');
            axios.get(config.baseApiUrl + 'logout', {
                headers: {
                    Authorization: 'Bearer ' + token
                }
            });
            this.$router.push('/cms/login');
        },
        getClients() {
            if (!config.storage.getItem('fwd_session_token')) return;
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + 'active-clients-list', this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status)
                    this.users = res.data.data;
                else {
                    console.log(res.data.error);
                }
            }).catch(er => {
                console.log(er.message);
            });
        },
    },
}
</script>
<style scoped>
.topbar-wrap {
    padding-top: 1em;
    background-color: #fff;
    padding-left: 0.6em;
    padding-right: 0.6em;
}

.top_bar {
    background-color: #eeeeee;
    border-radius: 1rem;
    padding: 14px 18px;
    margin-bottom: 15px;
}

.hamburger-btn {
    display: inline-flex;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    width: 42px;
    height: 42px;
    margin-right: 10px;
    padding: 10px 9px;
    border: none;
    border-radius: 10px;
    background: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    flex-shrink: 0;
    cursor: pointer;
}

.hamburger-btn span {
    display: block;
    height: 2px;
    width: 100%;
    background: #343434;
    border-radius: 2px;
    transition: transform 0.2s ease, opacity 0.2s ease;
}

.hamburger-btn.open span:nth-child(1) {
    transform: translateY(7px) rotate(45deg);
}

.hamburger-btn.open span:nth-child(2) {
    opacity: 0;
}

.hamburger-btn.open span:nth-child(3) {
    transform: translateY(-7px) rotate(-45deg);
}

.search-wrap {
    min-width: 0;
}

.client-search-input {
    background-color: white;
    border: 1px solid rgb(167, 166, 166);
    padding: 0.5rem 1rem 0.5rem 2.5rem;
    border-radius: 10px;
    width: 100%;
    min-width: 0;
}

.client-search-input:focus {
    outline: none;
}

.search-icon {
    max-height: 17px;
    top: 12px;
    left: 10px;
}

.client-search-results {
    top: 50px;
    background-color: white;
    max-height: 200px;
    width: 100%;
    z-index: 9999 !important;
    overflow-y: auto;
}

.topbar-actions {
    margin-left: 12px;
    flex-shrink: 0;
}

.notify-btn {
    padding: 8px 11px;
    width: 45px;
    height: 45px;
}

.unread-badge {
    top: -4px;
    right: -4px;
    height: 18px;
    width: 18px;
    color: white;
    text-align: center;
    font-size: 12px;
}

.prof-img {
    width: 45px;
    height: 45px;
    margin-right: 10px;
    margin-left: 10px;
}

.prof-img img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.unread {
    background-color: rgb(240, 240, 240);
    position: relative;
}

.unread::before {
    content: '';
    width: 10px;
    height: 10px;
    background-color: rgb(250, 0, 0);
    border-radius: 50%;
    position: absolute;
    top: 8px;
    right: 4px;
}

.profile-name-heading {
    font-size: 0.65rem;
    line-height: 1.1;
    white-space: nowrap;
}

.profile-name-text {
    display: inline-block;
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.profile-role {
    color: #000;
    font-size: 10px;
    line-height: 1.1;
    margin-top: 2px;
}

.notify {
    position: absolute;
    top: 48px;
    background-color: #fff;
    padding: 17px;
    right: 0;
    box-shadow: 0 0 10px 0px #f2a18c;
    width: min(350px, calc(100vw - 24px));
    border-radius: 16px;
    overflow: auto;
    height: min(500px, 70vh);
    z-index: 9;
}

.bar {
    flex-wrap: nowrap;
}

.client:hover {
    background-color: #F5F5F5;
    cursor: pointer;
}

@media screen and (max-width: 768px) {
    .topbar-wrap {
        padding-top: 0.6em;
        padding-left: 0.4em;
        padding-right: 0.4em;
    }

    .top_bar {
        padding: 10px 12px;
        margin-bottom: 10px;
    }

    .profile-dropdown {
        display: none;
    }

    .prof-img {
        margin-right: 0;
        margin-left: 6px;
    }

    .topbar-actions {
        margin-left: 8px;
    }

    .notify-btn {
        width: 40px;
        height: 40px;
        padding: 7px 9px;
    }

    .notify {
        right: -10px;
    }
}

@media screen and (max-width: 480px) {
    .hamburger-btn {
        width: 38px;
        height: 38px;
        margin-right: 8px;
    }

    .client-search-input {
        font-size: 13px;
        padding: 0.45rem 0.75rem 0.45rem 2.2rem;
    }
}
</style>
