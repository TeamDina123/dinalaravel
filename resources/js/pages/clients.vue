<template lang="">
    <div v-if="showfilters" @click.self="showfilters=false" class="my-popup-component clients-filter-overlay" style="justify-content:end">
            <div class="position-relative clients-filter-panel">
                <div v-if="tagsClose" style="height:100%;width:100%;position:absolute;z-index:999;top:0;left:0;" @click="closeTagsDiv"></div>
                <button @click="showfilters=false" class="trans_btn position-absolute" style="right:15px;top:10px;font-size:25px">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <p class="ms-4 mt-4" style="font-size:15px;">Clients Filters</p>
                <div class="w-100 clients-filter-body">
                    <button v-for="(item, index) in tags" :key="index" @click.self="openTagsDiv(index)" class="brds-1 mt-2 filterBtn float-left">
                        <p @click="openTagsDiv(index)" class="mb-0" v-if="item.tagType!==null">{{item.tagType}}</p>
                        <p @click="openTagsDiv(index)" class="mb-0" v-else>Un Categorized</p>
                        <img @click="openTagsDiv(index)" src="/cms-assets/images/Clients/downarrow.png" class="filterimg" alt="">
                        <div v-if="tagTypeShow[index]==true" class="tsl filter-tag-dropdown">
                            <div class="my-1" style="width:100%;height:20px;float:left;text-align:center;" v-for="(tagItem, tagIndex) in tags[index].tagList" :key="tagIndex">
                                <div class="mx-auto w-80">
                                    <input v-model="selectedTagsForFilter" :value="tagItem.name" type="checkbox" class="form-check-input me-1 float-start" style="margin-top:6px">
                                    <p class="ms-1 float-start" style="font-size:18px;margin:0px;color:#343434;">{{tagItem.name}}</p>
                                </div>
                            </div>
                        </div>
                    </button>
                </div>
                <div class="position-absolute bottom-0 w-100 mb-3 d-flex flex-wrap justify-content-center clients-filter-actions">
                    <button class="brds-2 mb-2 filter-action-btn" @click="tagsFilterApply">Apply</button>
                    <button class="brds-2 filter-action-btn" @click="tagsFilterApply(1)">Reset</button>
                </div>
            </div>
        </div>
    <div class="main brds-3 position-relative clients-page">
        <div class="top position-relative clients-top">
            <div class="clients-tabs">
                <button class="top-btn" :class="{ active: showSummary}" style="border-top-left-radius:10px;border-bottom-left-radius:10px;border-left:1px solid #C5C5C5" @click="navigate(1)">Summary</button>
                <button class="top-btn" :class="{ active: showExercise}" @click="navigate(2)">Exercise</button>
                <button class="top-btn" :class="{ active: showNutrition}" @click="navigate(3)">Nutrition</button>
                <button class="top-btn" :class="{ active: showWeight}" @click="navigate(4)">Weight</button>
                <button class="top-btn" :class="{ active: showPayment}" @click="navigate(5)">Payment</button>
                <button class="top-btn" :class="{ active: showEngagement}" style="border-top-right-radius:10px;border-bottom-right-radius:10px;" @click="navigate(6)">Engagement</button>
            </div>
            <div class="clients-top-actions d-flex align-items-center">
                <div class="px-1 brds-2 clients-search-box">
                    <img src="/cms-assets/images/navbar-topbar/search.png" alt="" style="height:15px;">
                    <input type="text" class="border-0 clients-search-input" v-model="searchValue" placeholder="Search for a client">
                </div>
                <div class="mx-2">
                    <button @click="showfilters=true" class="clients-filter-btn">
                        <img src="/cms-assets/images/master-libraries/filter.png" alt="" style="height:15px;" class="img-fluid">
                    </button>
                </div>
            </div>
        </div>
        <div class="content mx-auto">
            <div class="mx-auto clients-toolbar">
                <div class="dropdown float-start ms-2 " style="margin-top:1px;">
                    <button class="three-dots2 tsl brds-1 ms-1 position-relative" data-bs-toggle="dropdown" style="margin-top:9px;">
                        <img src="/cms-assets/images/master-libraries/three-dots.png" style="height:60%" alt="">
                    </button>
                    <ul class="dropdown-menu tsl">
                        <li><button class="dropdown-item" @click="addToGroup()">Add to Group</button></li>
                        <li><button class="dropdown-item" @click="ShowaddHabitPopup()">Add to Habit</button></li>
                        <li><button class="dropdown-item" @click="subscribeProgramPopup()">Subscribe to a program</button></li>
                    </ul>
                </div>
                <button @click.self="clientsDiv=!clientsDiv" class="mt-2 clients-status-btn">{{btnValue}}
                    <img src="/cms-assets/images/Clients/downarrow.png" class="clients-status-caret" >
                    <div v-if="clientsDiv" class="tsl clients-status-menu">
                        <p @click="changebtn(0)" class="w-100 m-0 py-2" style="height:33px;cursor:pointer;">All Clients</p>
                        <p @click="changebtn(1)" class="w-100 m-0 py-2" style="height:33px;cursor:pointer;">Active Clients</p>
                        <p @click="changebtn(2)" class="w-100 m-0 py-2" style="height:33px;cursor:pointer;">Pending Clients</p>
                        <p @click="changebtn(3)" class="w-100 m-0 py-2" style="height:33px;cursor:pointer;">Deactivate Clients</p>
                    </div>
                </button>
            </div>
            <div class="mx-auto clients-table-wrap">
                <div class="for_data_table">
                    <summaryTable v-if="showSummary==true && showTable" ref="DataTable" :userData="tagsFilteredClients" :searchValue="searchValue"/>
                    <exerciseTable v-if="showExercise==true && showTable" ref="DataTable" :userData="tagsFilteredClients"  :searchValue="searchValue"/>
                    <nutritionTable v-if="showNutrition==true && showTable" ref="DataTable" :userData="tagsFilteredClients"  :searchValue="searchValue"/>
                    <weightTable v-if="showWeight==true && showTable" ref="DataTable" :userData="tagsFilteredClients"  :searchValue="searchValue"/>
                    <paymentTable v-if="showPayment==true && showTable" ref="DataTable" :userData="tagsFilteredClients" :searchValue="searchValue"/>
                    <engagementTable v-if="showEngagement==true && showTable" ref="DataTable" :userData="tagsFilteredClients" :searchValue="searchValue"/>
                </div>
            </div>
        </div>
    </div>
    <div v-if="clientsDiv" class="position-absolute" style="height:100vh;width:100%;top:0;left:0;" @click="clientsDiv=false"></div>
    <addToGroup v-if="showAddToGroup" :selectedUsers="clientIds"/>
    <assignToTrainer v-if="showAssignToTrainer"/>
    <subscribeToProgram v-if="showSubscribeToProgram"/>
    <addHabit v-if="showAddHabit" :selectedUsers="clientIds"/>
    <clientPopup v-if="showClientPopup" :idForDetails="userId" :logInDetails="logInProps" @close="showClientPopup = false"/>
    <Loader v-if="pageLoading" :loadingText="loaderText" />
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail" />
    <p style="display:none;">{{tagsDisplayIndex}}</p>
</template>
<script>
import axios from 'axios';
import config from '../config';
import DataTable from 'datatables.net-vue3';
import Loader from '../components/loader.vue';
import Inform from '../components/inform.vue';
import addToGroup from '../components/clients/addToGroup.vue';
import assignToTrainer from '../components/clients/assignToTrainer.vue';
import addHabit from '../components/clients/addHabit.vue';
import clientPopup from '../components/clients/clientPopup.vue';
import summaryTable from '../components/clients/summaryTable.vue';
import exerciseTable from '../components/clients/exerciseTable.vue';
import nutritionTable from '../components/clients/nutritionTable.vue';
import weightTable from '../components/clients/weightTable.vue';
import paymentTable from '../components/clients/paymentTable.vue';
import engagementTable from '../components/clients/engagementTable.vue';
import subscribeToProgram from '../components/clients/subscribeToProgram.vue';
export default {
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    name: 'Clients',
    components: { DataTable, addToGroup, assignToTrainer, addHabit, clientPopup, Loader, Inform, summaryTable, exerciseTable, nutritionTable, weightTable, paymentTable, engagementTable, subscribeToProgram },
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            showSummary: true,
            showExercise: false,
            showNutrition: false,
            searchValue: '',
            userId: null,
            showWeight: false,
            showPayment: false,
            showEngagement: false,
            showAddToGroup: false,
            showAssignToTrainer: false,
            showAddHabit: false,
            showSubscribeToProgram: false,
            showClientPopup: false,
            pageLoading: false,
            loaderText: '',
            clientDetails: null,
            clientDetailsCopy: null,
            informModal: false,
            modalTitle: '',
            modalDetail: '',
            clientIds: [],
            btnValue: 'All Clients',
            btnValueForFilter: 'all',
            clientsDiv: false,
            showTable: false,
            filteredArray: [],
            showfilters: false,
            tags: [],
            tagTypeShow: [],
            tagsClose: false,
            selectedTagsForFilter: [],
            tagsFilteredClients: [],
            tagsBasedUserId: null,
            apiEndPoint: 'clients-summary',
        }
    },
    computed: {
        tagsDisplayIndex() {
            for (let index = 0; index < this.tagTypeShow.length; index++) {
                if (this.tagTypeShow[index] == true) {
                    this.tagsClose = true;
                    break
                }
                else {
                    this.tagsClose = false;
                }
            }
        }
    },
    mounted() {
        this.$emit('adminCheckEvent');
        this.getTags();
        if (this.$route.query.users) {
            let id = this.$route.query.users;
            this.tagsBasedUserId = window.atob(id);
            this.tagsBasedUserId = JSON.parse(this.tagsBasedUserId);
            this.getFilteredClients();
        }
        else {
            this.getClientSummary();
        }
    },
    watch: {
        $route(to, from) {
            console.log("route change");
            if (to.path === '/cms/clients') {
                console.log("query change");
                if (to.query !== from.query) {
                    console.log("query really change");
                    this.getClientSummary();
                }
            }
        }
    },
    methods: {
        getFilteredClients() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + this.apiEndPoint, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.clientDetails = [];
                    this.clientDetailsCopy = res.data.data;
                    for (let index = 0; index < this.clientDetailsCopy.length; index++) {
                        for (let index1 = 0; index1 < this.tagsBasedUserId.length; index1++) {
                            if (this.clientDetailsCopy[index].id == this.tagsBasedUserId[index1]) {
                                this.clientDetails.push(this.clientDetailsCopy[index])
                            }
                        }
                    }
                    this.filters();
                    this.tagsFilterApply();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true
                })
        },
        closeTagsDiv() {
            for (let index = 0; index < this.tagTypeShow.length; index++) {
                this.tagTypeShow[index] = false;
            }
        },
        openTagsDiv(m) {
            this.tagTypeShow[m] = true;
        },
        getTags() {
            axios.get(config.baseApiUrl + 'get-tags?category=client', this.apiConfig).then(res => {
                if (res.data.status) {
                    this.tags = res.data.data;
                    for (let index = 0; index < this.tags.length; index++) {
                        this.tagTypeShow.push('false')
                    }
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        async changebtn(m) {
            this.clientsDiv = false;
            this.showTable = false;
            this.filteredArray = [];
            if (m == 0) {
                this.btnValue = 'All Clients';
                this.btnValueForFilter = 'all';
            }
            else if (m == 1) {
                this.btnValue = 'Active Clients';
                this.btnValueForFilter = 'active';
            }
            else if (m == 2) {
                this.btnValue = 'Pending Clients';
                this.btnValueForFilter = 'pending';
            }
            else {
                this.btnValue = 'Deactivated Clients';
                this.btnValueForFilter = 'deactive';
            }
            await this.filters();
            this.tagsFilterApply();
            this.showTable = true;
        },
        filters() {
            this.filteredArray = [];
            if (this.btnValueForFilter == 'all') {
                this.filteredArray = this.clientDetails;
            }
            for (let index = 0; index < this.clientDetails.length; index++) {
                if (this.clientDetails[index].status == this.btnValueForFilter) {
                    this.filteredArray.push(this.clientDetails[index])
                }
            }
            this.tagsFilteredClients = this.filteredArray;
        },
        getClientSummary() {
            this.pageLoading = true;
            this.loaderText = 'Fetching';
            axios.get(config.baseApiUrl + this.apiEndPoint, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.clientDetails = res.data.data;
                    this.clientDetailsCopy = res.data.data;
                    this.filters();
                    this.tagsFilterApply();
                    this.showTable = true;
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            })
                .catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error!';
                    this.modalDetail = er;
                    this.informModal = true
                })
        },
        async tagsFilterApply(m) {
            this.tagsFilteredClients = [];
            this.showTable = false;
            if (this.selectedTagsForFilter.length < 1) {
                await this.applyFilters(1);
                this.showTable = true;
                this.showfilters = false;
                this.selectedTagsForFilter = [];
                return
            }
            if (m == 1) {
                await this.applyFilters(1);
                this.showTable = true;
                this.showfilters = false;
                this.selectedTagsForFilter = [];
            }
            else {
                await this.applyFilters();
                this.showTable = true;
                this.showfilters = false;
            }
        },
        applyFilters(m) {
            this.filteredArray.forEach(element1 => {
                if (element1.tagNames !== null) {
                    element1.tagNames.forEach(element2 => {
                        this.selectedTagsForFilter.forEach(element3 => {
                            if (element2 == element3) {
                                if (this.tagsFilteredClients.length == 0) {
                                    this.tagsFilteredClients.push(element1);
                                }
                                else {
                                    let check = false;
                                    this.tagsFilteredClients.forEach(element => {
                                        if (element1.id !== element.id) {
                                            check = true;
                                        }
                                        else {
                                            check = false;
                                        }
                                    });
                                    if (check == true) {
                                        this.tagsFilteredClients.push(element1);
                                    }
                                }
                            }
                        });
                    });
                }
            });
            if (m == 1) {
                this.tagsFilteredClients = this.filteredArray;
            }
        },
        navigate(n) {
            this.clientIds = [];
            let m = n;
            this.searchValue = '';
            if (m == 1) {
                this.showExercise = false;
                this.showSummary = true;
                this.showNutrition = false;
                this.showWeight = false;
                this.showPayment = false;
                this.showEngagement = false;
                this.apiEndPoint = 'clients-summary';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
            else if (m == 2) {
                this.showExercise = true;
                this.showSummary = false;
                this.showNutrition = false;
                this.showWeight = false;
                this.showPayment = false;
                this.showEngagement = false;
                this.apiEndPoint = 'clients-exercises';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
            else if (m == 3) {
                this.showExercise = false;
                this.showSummary = false;
                this.showNutrition = true;
                this.showWeight = false;
                this.showPayment = false;
                this.showEngagement = false;
                this.apiEndPoint = 'clients-nutrition';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
            else if (m == 4) {
                this.showExercise = false;
                this.showSummary = false;
                this.showNutrition = false;
                this.showWeight = true;
                this.showPayment = false;
                this.showEngagement = false;
                this.apiEndPoint = 'clients-weight';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
            else if (m == 5) {
                this.showExercise = false;
                this.showSummary = false;
                this.showNutrition = false;
                this.showWeight = false;
                this.showPayment = true;
                this.showEngagement = false;
                this.apiEndPoint = 'clients-payments';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
            else if (m == 6) {
                this.showExercise = false;
                this.showSummary = false;
                this.showNutrition = false;
                this.showWeight = false;
                this.showPayment = false;
                this.showEngagement = true;
                this.apiEndPoint = 'clients-engagement';
                if (this.$route.query.users) {
                    this.getFilteredClients();
                }
                else {
                    this.getClientSummary();
                }
            }
        },
        addToGroup() {
            if (this.clientIds.length > 0) {
                this.showAddToGroup = true;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No client selected';
                this.informModal = true;
            }
        },
        closeAddToGroup(m) {
            if (m == 1) {
                this.modalTitle = 'Done'
                this.modalDetail = 'Clients added to group';
                this.informModal = true;
                this.showAddToGroup = false;
                this.$refs.DataTable.removeSelected();
            }
            this.showAddToGroup = false;
        },
        ShowaddHabitPopup() {
            if (this.clientIds.length > 0) {
                this.showAddHabit = true;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No client selected';
                this.informModal = true;
            }
        },
        CloseaddHabitPopup(m) {
            this.showAddHabit = false;
            if (m == 1) {
                this.modalTitle = 'Done';
                this.modalDetail = 'Habit assigned to clients successfully';
                this.informModal = true;
                this.$refs.DataTable.removeSelected();
            }
        },
        assignToTrainer() {
            this.showAssignToTrainer = !this.showAssignToTrainer
        },
        ClientPopup(m) {
            this.userId = m;
            this.showClientPopup = !this.showClientPopup;
        },
        getClientIds(m) {
            this.clientIds = m;
        },
        subscribeProgramPopup() {
            if (this.clientIds.length > 0) {
                this.showSubscribeToProgram = !this.showSubscribeToProgram;
            }
            else {
                this.modalTitle = 'Error!';
                this.modalDetail = 'No client selected';
                this.informModal = true;
            }
        },
        subscribeProgram(l) {
            this.showSubscribeToProgram = false;
            const postData = {
                'program_id': l,
                'ids': this.clientIds,
            }
            this.pageLoading = true;
            this.loaderText = 'Uploading';
            axios.post(config.baseApiUrl + 'subscribe-users-to-program', postData, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.modalDetail = 'Program subscribed succesfully';
                    this.informModal = true;
                    this.clientDetails = null;
                    this.getClientSummary();
                    this.$refs.DataTable.removeSelected();
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er;
                this.informModal = true
            });
        },
        acknowledged() {
            this.informModal = false;
        }
    }
}
</script>
<style scoped>
@import 'datatables.net-dt';

.main {
    width: 100%;
    border: 1px solid #E7E7E7;
    height: calc(100vh - 125px);
    border-radius: 15px;
}

.top {
    min-height: 50px;
    width: 100%;
    background-color: #F7F7F7;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
    border: none;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 6px 12px 10px;
}

.clients-tabs {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    max-width: 100%;
    padding-bottom: 2px;
}

.clients-top-actions {
    margin-left: auto;
    flex-shrink: 0;
}

.top-btn {
    margin-top: 4px;
    height: 30px;
    font-size: 13px;
    color: #343434ee;
    border: 1px solid #C5C5C5;
    border-left: none;
    background-color: #FFFFFF;
    white-space: nowrap;
    padding: 0 12px;
    flex-shrink: 0;
}

.clients-search-box {
    background-color: white;
    border: 1px solid #C5C5C5;
    display: flex;
    align-items: center;
    gap: 4px;
}

.clients-search-input {
    font-size: 12px;
    height: 24px;
    min-width: 140px;
    outline: none;
}

.clients-filter-btn {
    border: none;
    background-color: transparent;
    padding-top: 4px;
}

.clients-toolbar {
    width: 98%;
    min-height: 50px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.clients-status-btn {
    height: 35px;
    width: 235px;
    max-width: 100%;
    font-size: 12px;
    color: #606060;
    background-color: white;
    position: relative;
    border: 1px solid #C5C5C5;
    text-align: left;
    border-radius: 10px;
    padding-right: 22px;
}

.clients-status-caret {
    position: absolute;
    height: 7px;
    right: 5px;
    top: 13px;
}

.clients-status-menu {
    position: absolute;
    width: 98%;
    height: 135px;
    z-index: 9999;
    background-color: white;
    left: 1%;
    border-radius: 10px;
    font-size: 12px;
    text-align: center;
}

.clients-table-wrap {
    min-height: 370px;
    width: 98%;
}

.for_data_table {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.for_data_table :deep(.vue3-easy-data-table) {
    min-width: 720px;
}

.content {
    height: calc(100% - 58px);
    width: 99%;
    overflow-y: auto;
}

.three-dots2 {
    padding: 0px;
    height: 25px;
    border: none;
    background-color: inherit;
    text-align: center;
    width: 25px;
}

.active {
    background-color: #F2A18C;
    border: none;
}

.clients-filter-panel {
    width: 330px;
    max-width: 100%;
    height: 100vh;
    background-color: white;
    border-top-left-radius: 20px;
    border-bottom-left-radius: 20px;
}

.clients-filter-body {
    height: calc(100% - 165px);
    overflow-y: auto;
    text-align: center;
}

.filter-tag-dropdown {
    width: 255px;
    max-width: calc(100% - 8px);
    height: 150px;
    position: absolute;
    top: 30px;
    left: 2px;
    z-index: 9999 !important;
    background-color: white;
    overflow-y: auto;
}

.filter-action-btn {
    width: 180px;
    max-width: 90%;
    height: 40px;
    border: none;
    background-color: #F2A18C;
    font-size: 16px;
    color: #FFFFFF;
}

.filterBtn {
    height: 40px;
    width: 260px;
    max-width: 90%;
    font-size: 16px;
    color: #F2A18C;
    text-align: center;
    border: 1px solid #B1B0B0;
    background-color: white;
    position: relative;
}

.filterimg {
    position: absolute;
    right: 20px;
    top: 15px;
    height: 8px;
}

@media screen and (max-width: 992px) {
    .main {
        height: calc(100vh - 140px);
        min-height: 480px;
    }

    .content {
        height: calc(100% - 70px);
    }
}

@media screen and (max-width: 768px) {
    .main {
        height: auto;
        min-height: calc(100vh - 150px);
        border-radius: 12px;
    }

    .top {
        padding: 8px 8px 10px;
        gap: 10px;
    }

    .clients-tabs {
        width: 100%;
        order: 1;
    }

    .clients-top-actions {
        width: 100%;
        order: 2;
        margin-left: 0;
        justify-content: space-between;
    }

    .clients-search-box {
        flex: 1;
    }

    .clients-search-input {
        width: 100%;
        min-width: 0;
        flex: 1;
    }

    .top-btn {
        font-size: 12px;
        padding: 0 10px;
        height: 32px;
    }

    .clients-toolbar {
        width: 100%;
        padding: 0 4px;
        flex-wrap: wrap;
    }

    .clients-status-btn {
        width: min(235px, 100%);
        margin-left: auto;
    }

    .clients-table-wrap {
        width: 100%;
        min-height: 300px;
        padding-bottom: 16px;
    }

    .content {
        width: 100%;
        height: auto;
        overflow: visible;
    }

    .clients-filter-panel {
        width: min(330px, 100vw);
        border-radius: 20px 0 0 20px;
    }

    .filterBtn {
        width: min(260px, 88%);
    }
}

@media screen and (max-width: 480px) {
    .top-btn {
        font-size: 11px;
        padding: 0 8px;
    }

    .clients-status-btn {
        width: 100%;
        float: none;
    }

    .clients-toolbar {
        align-items: flex-start;
    }
}
</style>
