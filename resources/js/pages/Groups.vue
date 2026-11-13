<template lang="">
    <div @click.self="memberDetail=false" v-if="memberDetail" style="height:100vh;width:100vw;background-color:transparent;position:absolute;top:0;left:0;z-index:99"></div>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Preview v-if="previewOn" :file="previewFile" :filetype="previewType"/>
    <VoiceSendTargetModal
        v-if="voiceTargetModal"
        page-mode="groups"
        :current-group-id="groupProps.active ? groupProps.active.id : null"
        @cancel="voiceTargetModal = false"
        @record="startVoiceRecord"
        @upload="startVoiceUpload"
    />
    <ForwardMessageModal
        v-if="forwardModal"
        :message="forwardMessageData"
        source-type="group"
        @cancel="forwardModal = false"
        @forwarded="onMessageForwarded"
    />
    <Loader v-if="pageLoading" :loadingText="loadingText"/>
    <!-- <groupMembersVue v-if="groupMemberAdd" :groupId="idForAction"/> -->
    <!-- <GetInput v-if="false" :msgTitle="modalTitle" :msgDetail="modalDetail"/> -->
    <div class="groups-page">
        <div class="d-flex w-100 msgs-box" :class="{ 'mobile-convo-open': groupProps.active !== null }">
            <div class="chats-side">
                <div class="chats-head p-2">
                    <div class="position-relative">
                        <input type="text" class="w-100 ps-4" placeholder="Search Groups" v-model="search" />
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon"
                            class="img-fluid position-absolute" />
                    </div>
                    <div class="mt-3 position-relative groups-toolbar">
                        <button @click="openNewGroup()" class="prim_btn py-1 h7 brds-1 groups-add-btn">Add Group</button>
                        <select v-model="tabFilter" class="groups-filter-select">
                            <option value="all" selected>All Groups</option>
                            <option value="my" >My Groups</option>
                        </select>
                    </div>
                </div>
                <div class="chats-list my-1">
                    <div v-if="groupProps.groups.length==0" class="mt-3 text-center">
                        <p>No Groups Yet</p>
                    </div>
                    <div v-else-if="filteredGroupsSearch.length<1" class="mt-3 text-center">
                        <p>No Group for the filter</p>
                    </div>
                    <div v-for="groupSingle in filteredGroupsSearch" :key="groupSingle.id">
                        <GroupItem :groupInfo="groupSingle"/>
                    </div>
                </div>
            </div>
            <div class="convo-side">
                <div v-if="groupProps.active===null" class="desktop-empty-state" style="overflow-y:auto;height:calc(100vh - 177px)">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="px-3">
                            <img src="/cms-assets/images/no-message.png" alt="" class="img-fluid d-block mx-auto" style="max-height:250px">
                            <div class="mt-3 text-center">
                                <h5 class="mb-2"><strong>Select a group to view</strong></h5>
                                <p class="mb-0 text-muted">Stay connected with your clients and fellow team members with one-to-one or group private messages</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="groupProps.active.members===0" class="h-100 d-flex flex-column">
                    <div class="convo-head p-3 d-flex align-items-center">
                        <button type="button" class="mobile-back-btn trans_btn me-1" @click="closeMobileConvo" aria-label="Back to groups list">
                            <i class="fa-solid fa-arrow-left"></i>
                        </button>
                        <p class="h7 mb-0 text-capitalize"><strong>{{groupProps.active.name}}</strong></p>
                    </div>
                    <div class="d-flex flex-column justify-content-center align-items-center flex-grow-1">
                        <div class="text-center px-3">
                            <h3 class="mb-2 fw-bold">No Members Assigned Yet</h3>
                            <button class="prim_btn brds-2 fw-bold" @click="addMembers(null,groupProps.active.id)">Add Members</button>
                        </div>
                    </div>
                </div>
                <div v-else class="h-100 d-flex flex-column">
                    <div class="convo-head p-3 d-flex justify-content-between position-relative align-items-center">
                        <div class="d-flex align-items-center pointer flex-grow-1 min-w-0" @click="toggleMembersDetail()">
                            <button type="button" class="mobile-back-btn trans_btn me-1" @click.stop="closeMobileConvo" aria-label="Back to groups list">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <div class="px-2 align-self-center flex-shrink-0">
                                <img :src="groupProps.active.image" alt="" class="rounded-circle" style="width:40px;height:40px"/>
                            </div>
                            <div class="min-w-0">
                                <div>
                                    <p class="h7 mb-0 text-capitalize text-truncate"><strong>{{groupProps.active.name}}</strong></p>
                                </div>
                                <div>
                                    <p style="color: #f2a18c" class="h8 mb-0">{{groupProps.active.members}} Members</p>
                                </div>
                            </div>
                        </div>
                        <div tabindex="0" @focusout="memberDetail=false" v-if="memberDetail" class="shd_card p-3 members-detail">
                            <div v-for="member in membersList" :key="member.id || member.name" class="d-flex justify-content">
                                <p class="text-capitalize px-2 py-1 m-0">{{member.name}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="convo-content flex-grow-1">
                        <div class="d-flex flex-column justify-content-end h-100">
                            <div id="messageDiv" class="px-3 px-md-4 pb-4 message-scroll" ref="scrollable">
                                <MsgItem v-for="msg in groupProps.messages" :key="msg.id" :msgdet="msg"/>
                            </div>
                        </div>
                    </div>
                    <div class="convo-type">
                        <div class="convo-type-input">
                            <textarea type="text" rows="1" class="w-100"
                            @keyup.exact.enter="checkKey('send')"
                            @keyup.exact.enter.shift="checkKey('break')"
                            placeholder="Type a message..."
                            v-model="newMessage"></textarea>
                        </div>
                        <div class="convo-type-actions d-flex align-items-center">
                            <button class="trans_btn position-relative" type="button">
                                <img @click="toogleEmojiPicker" style="max-height:25px" src="/cms-assets/images/Shape.png" class="img-fluid" alt="emoji">
                                <EmojiPicker v-if="emojiPicker"/>
                            </button>
                            <button @click="openImageInput()" class="trans_btn" type="button">
                                <img style="max-height:25px" src="/cms-assets/images/image.png" class="img-fluid" alt="image">
                            </button>
                            <input @change="getImageFile()" type="file" accept=".jpg, .png, .jpeg" class="d-none" ref="imageFileRef">
                            <button @click="openVideoInput()" class="trans_btn" type="button">
                                <img style="max-height:25px" src="/cms-assets/images/video.png" class="img-fluid" alt="video">
                            </button>
                            <input @change="getVideoFile()" type="file" accept=".mp4, .mkv, .mov, .m4v" class="d-none" ref="videoFileRef">
                            <button @click="openDocInput()" class="trans_btn" type="button">
                                <img style="max-height:25px" src="/cms-assets/images/document.png" class="img-fluid" alt="document">
                            </button>
                            <input @change="getDocFile()" type="file" accept=".docx,.pdf,.txt,.doc" class="d-none" ref="docFileRef">
                            <button class="trans_btn" type="button" @click="openVoiceTargetModal()">
                                <img style="max-height:25px" src="/cms-assets/images/audio.png" class="img-fluid" alt="audio">
                            </button>
                            <input @change="getAudioFile()" type="file" accept=".mp3,.wav,.m4a,.webm,.ogg,.aac,audio/*" class="d-none" ref="audioFileRef">
                            <button @click="sendTextMessage()" class="trans_btn" type="button">
                                <img style="max-height:35px" src="/cms-assets/images/send.png" class="img-fluid" alt="send">
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div v-if="emojiPicker" class="position-absolute" style="height: 100vh;width: 100vw;top: 0;left: 0;" @click.self="toogleEmojiPicker">
    </div>
    <createGroup v-if="showGroup"/>
    <addGroupMember v-if="groupMemberAdd" :groupId="idForAction"/>
    <renameGroup v-if="getInputOn" :renameValue="renameValue"/>
    <removeGroup v-if="deletePopup" :msgTitle="modalTitle" :msgDetail="modalDetail" :btnValue1="btn1Value" :btnValue2="btn2Value"/>
</template>
<script>
import config from "../config";
import MsgItem from "../components/groups/msg-item.vue";
import Inform from '../components/inform.vue';
import Confirm from '../components/confirm.vue';
import Loader from '../components/loader.vue';
import Preview from '../components/messages/preview.vue';
import VoiceSendTargetModal from '../components/messages/VoiceSendTargetModal.vue';
import ForwardMessageModal from '../components/messages/ForwardMessageModal.vue';
import EmojiPicker from '../components/messages/EmojiPicker.vue';
import createGroup from "../components/groups/createGroup.vue";
import addGroupMember from "../components/groups/addGroupMember.vue";
import groupSettings from "../components/groups/groupSettings.vue";
import renameGroup from "../components/groups/renameGroup.vue";
import removeGroup from "../components/groups/removeGroup.vue";
import GroupItem from "../components/groups/group-item.vue";
import axios from "axios";
import groupMembersVue from "../components/groups/groupMembers.vue";
import GetInput from "../components/getInput.vue";

//  Vue.use(AudioRecorder);
export default {
    components: {
        GroupItem, MsgItem, Inform, Confirm, Loader, Preview, groupMembersVue, GetInput,
        EmojiPicker, createGroup, addGroupMember, groupSettings, renameGroup, removeGroup,
        VoiceSendTargetModal, ForwardMessageModal
    },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent',
        'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
    props: ['groupProps', 'chatProps', 'logInProps'],
    data() {
        return {
            apiConfig: {
                headers: {
                    'Authorization': 'Bearer ' + config.storage.getItem('fwd_session_token')
                }
            },
            emojiPicker: false,
            newMessage: null,
            getInputVisi: false,
            getInputTitle: '',
            getInputDetail: '',
            firstMsgTo: '',
            previewOn: false,
            previewFile: null,
            previewType: null,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            pageLoading: false,
            loadingText: "Loading",
            search: "",
            showGroup: false,
            idForAction: null,
            actionType: '',
            groupMemberAdd: false,
            getInputOn: false,
            renameValue: null,
            btn1Value: null,
            btn2Value: null,
            deletePopup: false,
            memberDetail: false,
            membersList: [],
            tabFilter: 'all',
            voiceTargetModal: false,
            voiceSendTarget: null,
            forwardModal: false,
            forwardMessageData: null,
        };
    },
    mounted() {
        this.$emit("adminCheckEvent");
        this.$emit("getGroupsEvent", 1);
    },
    beforeUnmount() {
        this.groupProps.active = null;
    },
    computed: {
        filteredGroupsSearch() {
            return this.filterGroupsTab.filter((chatData) => {
                return chatData.name.toLowerCase().includes(this.search.toLowerCase());
            });
        },
        filterGroupsTab() {
            if (this.tabFilter == 'my') {
                return this.groupProps.groups.filter((chatData) => {
                    return chatData.label.toLowerCase().includes(this.tabFilter.toLowerCase());
                })
            }
            else {
                return this.groupProps.groups
            }
        }
    },
    methods: {
        toogleEmojiPicker() {
            this.emojiPicker = !this.emojiPicker;
        },
        changeGroupType(id) {
            this.groupProps.active = null;
            this.pageLoading = true;
            this.loadingText = 'Changing Group Type';
            axios.get(config.baseApiUrl + 'switch-group-label/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done';
                    this.modalDetail = 'Group Type Changed Successfully';
                    this.informModal = true;
                    this.$emit("getGroupsEvent");
                }
                else {
                    this.modalTitle = 'Error!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error!';
                this.modalDetail = er.message;
                this.informModal = true;
            })
        },
        deleteMessage(id) {
            this.pageLoading = true;
            this.loadingText = 'Deleteing';
            axios.get(config.baseApiUrl + 'delete-group-message/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.$emit('getGroupsEvent');
                }
                else
                    this.modalTitle = 'Error!';
                this.modalDetail = res.data.message;
                this.informModal = true;
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Failed!';
                this.modalDetail = er.message;
                this.informModal = true;
            });
        },
        createNewGroup(name, access, image) {
            this.loadingText = "Creating Group";
            this.pageLoading = true;
            let fd = new FormData();
            const apiCon = {
                "headers": {
                    "Authorization": "Bearer " + config.storage.getItem('fwd_session_token'),
                    "Content-Type": "multipart/form-data"
                }
            }
            fd.append('image', image);
            fd.append('name', name);
            fd.append('msg_access', access);
            axios.post(config.baseApiUrl + 'create-group', fd, apiCon).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.showGroup = false;
                    this.$emit("getGroupsEvent");
                } else {
                    this.modalTitle = 'Failed';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.pageLoading = false;
                this.modalTitle = 'Error';
                this.modalDetail = 'Something Went Wrong';
                console.log("create group error: ", er.message);
                this.informModal = true;
            });
        },
        addMembers(usersArray, groupId) {
            if (usersArray === null) {
                this.idForAction = groupId;
                this.groupMemberAdd = true;
            } else {
                this.groupMemberAdd = false;
                const postData = {
                    "group_id": this.idForAction,
                    "user_ids": usersArray
                }
                this.loadingText = "Adding Members"
                this.pageLoading = true;
                axios.post(config.baseApiUrl + 'add-members', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$emit("getGroupsEvent");
                        this.groupProps.active = null;
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("create group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        updateScroll() {
            setTimeout(() => {
                let element = document.getElementById("messageDiv");
                element.scrollTop = element.scrollHeight;
            }, 300)
        },
        toggleMembersDetail() {
            this.membersList = [];
            if (!this.memberDetail) {
                this.loadingText = 'Loading';
                axios.get(config.baseApiUrl + 'group-members/' + this.groupProps.active.id, this.apiConfig).then(res => {
                    if (res.data.status)
                        this.membersList = res.data.data;
                }).catch(er => {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("group members error: ", er.message);
                    this.informModal = true;
                })
            }
            this.memberDetail = !this.memberDetail;
        },
        closeMobileConvo() {
            this.memberDetail = false;
            this.emojiPicker = false;
            this.groupProps.active = null;
        },
        openGroup(group) {
            this.$emit('activeGroupEvent', group, 1);
        },
        sendTextMessage() {
            this.newMessage = this.newMessage.trim();
            if (this.newMessage == null || this.newMessage == '')
                return;
            const postData = {
                group_id: this.groupProps.active.id,
                content: this.newMessage
            }
            this.newMessage = '';
            axios.post(config.baseApiUrl + 'send-group-text-message', postData, this.apiConfig).then(res => {
                if (res.data.status)
                    this.$emit('getGroupMessagesEvent', res.data.sent_msg);
                else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            });
        },
        sendFileMessage(file) {
            this.previewOn = false;
            const apiCon = {
                "headers": {
                    "Authorization": "Bearer " + config.storage.getItem('fwd_session_token'),
                    "Content-Type": "multipart/form-data"
                }
            }
            let fd = new FormData();
            fd.append('file', file);

            const target = this.voiceSendTarget;
            let endpoint = 'send-group-file-message';
            if (target?.type === 'chat') {
                endpoint = 'send-file-message';
                fd.append('chat_id', target.chatId);
            } else if (target?.type === 'program') {
                endpoint = 'multiple-users-file-message';
                target.userIds.forEach((id) => fd.append('user_ids[]', id));
            } else {
                const groupId = target?.groupId || (this.groupProps.active ? this.groupProps.active.id : null);
                if (!groupId) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Select a group before sending a file.';
                    this.informModal = true;
                    return;
                }
                fd.append('group_id', groupId);
            }

            axios.post(config.baseApiUrl + endpoint, fd, apiCon).then(res => {
                this.voiceSendTarget = null;
                if (res.data.status) {
                    if (endpoint === 'send-group-file-message') {
                        this.$emit('getGroupMessagesEvent', res.data.sent_msg);
                    } else if (endpoint === 'send-file-message') {
                        this.$emit('getMessagesEvent', res.data.sent_msg);
                    } else {
                        this.modalTitle = 'Done';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                } else {
                    this.modalTitle = 'Failed!';
                    this.modalDetail = res.data.message;
                    this.informModal = true;
                }
            }).catch(er => {
                this.voiceSendTarget = null;
                this.modalTitle = 'Error!';
                this.modalDetail = 'Something Went Wrong';
                this.informModal = true;
            });
        },
        openNewGroup() {
            this.showGroup = !this.showGroup;
        },
        openImageInput() {
            this.$refs.imageFileRef.click();
        },
        openVideoInput() {
            this.$refs.videoFileRef.click();
        },
        openDocInput() {
            this.$refs.docFileRef.click();
        },
        openVoiceTargetModal() {
            this.voiceTargetModal = true;
        },
        startVoiceRecord(target) {
            this.voiceSendTarget = target;
            this.voiceTargetModal = false;
            this.previewFile = null;
            this.previewType = 'audio';
            this.previewOn = true;
        },
        startVoiceUpload(target) {
            this.voiceSendTarget = target;
            this.voiceTargetModal = false;
            this.$refs.audioFileRef.click();
        },
        openForwardMessage(message) {
            this.forwardMessageData = message;
            this.forwardModal = true;
        },
        onMessageForwarded(result) {
            this.forwardModal = false;
            this.modalTitle = 'Done';
            this.modalDetail = result.message || 'Message forwarded.';
            this.informModal = true;
            if (result.target_type === 'group') {
                this.$emit('getGroupsEvent', 1);
            } else {
                this.$emit('getConvosEvent', 1);
            }
        },
        getAudioInput() {
            this.openVoiceTargetModal();
        },
        getImageFile() {
            let tempFile = this.$refs.imageFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "image";
                this.previewOn = true;
            }
        },
        getVideoFile() {
            let tempFile = this.$refs.videoFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "video";
                this.previewOn = true;
            }
        },
        getDocFile() {
            let tempFile = this.$refs.docFileRef.files[0];
            if (tempFile) {
                this.previewFile = tempFile;
                this.previewType = "document";
                this.previewOn = true;
            }
        },
        getAudioFile() {
            const tempFile = this.$refs.audioFileRef.files[0];
            if (!tempFile) {
                return;
            }
            if (!this.voiceSendTarget) {
                this.voiceTargetModal = true;
                return;
            }
            this.previewFile = tempFile;
            this.previewType = 'audio';
            this.previewOn = true;
            this.$refs.audioFileRef.value = '';
        },
        closeEmojiInput() {
            this.emojiPicker = false;
        },
        getEmojiInput(emoji) {
            if (this.newMessage == null)
                this.newMessage = '';
            this.newMessage = this.newMessage + emoji;
        },
        checkKey(eve) {
            if (eve === 'send')
                this.sendTextMessage();
        },
        closePreviewComp() {
            this.previewOn = false;
        },
        quitRename() {
            this.getInputOn = false;
        },
        renameGroup(id, newname, oldname) {
            this.groupProps.active = null;
            this.getInputOn = !this.getInputOn;
            if (newname === null) {
                this.idForAction = id;
                this.renameValue = oldname;
            } else {
                const postData = {
                    "group_id": this.idForAction,
                    "name": newname
                }
                this.pageLoading = true;
                axios.post(config.baseApiUrl + 'rename-group', postData, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("rename group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        leaveGroup(id, type, name) {
            this.groupProps.active = null;
            this.deletePopup = !this.deletePopup;
            if (type === 'confirm') {
                this.idForAction = id;
                this.modalTitle = 'Leave and delete group?';
                this.modalDetail = 'Do you want to leave  ' + name + ' ? Because leaving will also delete this group.'
                this.btn1Value = 'Leave & Delete';
                this.btn2Value = 'Cancel';
                this.actionType = 'leaveGroup';
            } else {
                this.pageLoading = true;
                this.loadingText = 'Leaving';
                axios.get(config.baseApiUrl + 'leave-group/' + this.idForAction, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.groupProps.active = null;
                        this.deletePopup = false;
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("leave group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        quitDelete() {
            this.deletePopup = false;
        },
        deleteGroup(id, type, name) {
            this.groupProps.active = null;
            this.deletePopup = !this.deletePopup;
            if (type === 'confirm') {
                this.idForAction = id;
                this.modalTitle = 'Delete group?';
                this.modalDetail = 'This group’s message history will be deleted. This cannot be undone.';
                this.btn1Value = 'Delete';
                this.btn2Value = 'Cancel';
                this.actionType = 'deleteGroup';
            } else {
                this.pageLoading = true;
                this.loadingText = 'Deleting';
                axios.get(config.baseApiUrl + 'delete-group/' + this.idForAction, this.apiConfig).then(res => {
                    this.pageLoading = false;
                    if (res.data.status) {
                        this.groupProps.active = null;
                        this.$emit("getGroupsEvent");
                    } else {
                        this.modalTitle = 'Failed';
                        this.modalDetail = res.data.message;
                        this.informModal = true;
                    }
                }).catch(er => {
                    this.pageLoading = false;
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Something Went Wrong';
                    console.log("delete group error: ", er.message);
                    this.informModal = true;
                });
            }
        },
        acknowledged() {
            this.informModal = false;
        },
        confirmationResponse(resp) {
            this.confirmModal = false;
            if (resp === 1) {
                if (this.actionType === 'leaveGroup')
                    this.leaveGroup(null, 'confirmed', null);
                if (this.actionType === 'deleteGroup')
                    this.deleteGroup(null, 'confirmed', null);
            }
        },
        inputResponse(value) {
            this.getInputOn = false;
            if (value === null)
                return;
            this.renameGroup(null, value, null);
        },
    },
};
</script>
<style scoped>
.groups-page {
    width: 100%;
    max-width: 100%;
    overflow: hidden;
}

.min-w-0 {
    min-width: 0;
}

textarea {
    border: none;
    padding-top: 12px;
    padding-left: 16px;
    padding-bottom: 10px;
    resize: none;
}

.chats-side {
    background-color: #eeeeee;
    border-top-left-radius: 1rem;
    border-bottom-left-radius: 1rem;
    border-right: 1px solid rgb(228, 228, 228);
    overflow: auto;
    width: 270px;
    flex-shrink: 0;
}

.convo-side {
    width: calc(100% - 270px);
    min-width: 0;
    background: #fff;
}

.msgs-box {
    border: 1px solid rgb(228, 228, 228);
    border-radius: 1rem;
    height: calc(100vh - 127px);
    overflow: hidden;
}

.chats-head input,
.chats-head select {
    padding: 10px;
    border-radius: 10px;
    font-size: 12px;
    border: 1px solid rgb(192, 192, 192);
    background-color: white;
    width: 100%;
    color: gray;
}

.groups-toolbar {
    height: 29px;
    display: flex;
    gap: 4px;
}

.groups-add-btn {
    width: 40%;
    font-size: 12px;
    height: 100%;
}

.groups-filter-select {
    width: 59%;
    height: 100%;
    padding: 0;
    border-radius: 5px !important;
}

.chats-head input:focus,
.chats-head select:focus {
    outline: none;
}

.chats-head img {
    height: 12px;
    left: 7px;
    top: 13px;
}

.chats-list {
    border-top: 1px solid rgb(216, 216, 216);
}

.convo-head {
    border-bottom: 1px solid gainsboro;
    flex-shrink: 0;
}

.convo-head img {
    width: 35px;
    height: 35px;
    border-radius: 50%;
}

.mobile-back-btn {
    display: none;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    font-size: 16px;
    flex-shrink: 0;
}

.convo-content {
    height: calc(100% - 125px);
    min-height: 0;
}

.message-scroll {
    overflow-y: auto;
    height: 100%;
    -webkit-overflow-scrolling: touch;
}

.convo-type {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center;
    border-top: 1px solid #dbdbdb;
    flex-shrink: 0;
    gap: 4px;
    padding-right: 4px;
}

.convo-type-input {
    flex: 1 1 auto;
    min-width: 0;
    border-right: 1px solid #dbdbdb;
}

.convo-type-actions {
    flex: 0 0 auto;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding: 4px 6px;
}

.convo-type input {
    border: none;
    padding-top: 14px;
    padding-bottom: 14px;
    padding-left: 25px;
    width: 100%;
}

.ch-opt>* {
    border: 1px solid rgb(170, 170, 170);
    margin: 0;
    width: 100%;
}

.ch-opt>*:first-child {
    border-top-left-radius: 5px;
    border-bottom-left-radius: 5px;
    border-right: 0;
}

.ch-opt>*:last-child {
    border-top-right-radius: 5px;
    border-bottom-right-radius: 5px;
    border-left: 0;
}

.members-detail {
    position: absolute;
    left: 100px;
    top: 60px;
    width: 206px;
    max-width: calc(100vw - 48px);
    max-height: 300px;
    overflow-y: auto;
    z-index: 99;
}

.members-detail div {
    border-bottom: 1px solid rgb(197, 197, 197);
}

@media screen and (max-width: 991.98px) {
    .msgs-box {
        height: calc(100dvh - 150px);
        min-height: 420px;
        border-radius: 12px;
    }

    .chats-side,
    .convo-side {
        width: 100%;
        border-radius: 12px;
        border-right: none;
    }

    .msgs-box:not(.mobile-convo-open) .convo-side {
        display: none;
    }

    .msgs-box.mobile-convo-open .chats-side {
        display: none;
    }

    .mobile-back-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .convo-content {
        height: auto;
        flex: 1 1 auto;
    }

    .convo-type {
        flex-wrap: wrap;
    }

    .convo-type-input {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #dbdbdb;
    }

    .convo-type-actions {
        width: 100%;
        justify-content: space-between;
    }

    .desktop-empty-state {
        display: none !important;
    }

    .members-detail {
        left: 16px;
        right: 16px;
        width: auto;
    }
}

@media screen and (max-width: 576px) {
    .msgs-box {
        height: calc(100dvh - 145px);
    }

    .convo-head {
        padding: 10px 8px !important;
    }

    .message-scroll {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }
}
</style>
