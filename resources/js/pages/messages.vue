<template lang="">
    <NewChat v-if="newChatCompVisib"/>
    <ClientPopup v-if="profileClientId" :idForDetails="profileClientId" :logInDetails="logInProps" @close="profileClientId = null"/>
    <Inform v-if="informModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Confirm v-if="confirmModal" :msgTitle="modalTitle" :msgDetail="modalDetail"/>
    <Preview v-if="previewOn" :file="previewFile" :filetype="previewType"/>
    <VoiceSendTargetModal
        v-if="voiceTargetModal"
        page-mode="messages"
        :current-chat-id="chatProps.active ? chatProps.active.id : null"
        @cancel="voiceTargetModal = false"
        @record="startVoiceRecord"
        @upload="startVoiceUpload"
    />
    <ForwardMessageModal
        v-if="forwardModal"
        :message="forwardMessageData"
        source-type="chat"
        @cancel="forwardModal = false"
        @forwarded="onMessageForwarded"
    />
    <Loader v-if="pageLoading" :loadingText="loadingText"/>
    <GetInput v-if="firstMsgVisi" :msgTitle="firstMsgTitle" :msgDetail="firstMsgDetail"/>
    <div v-if="tagsDiv" style="position:fixed;height:100vh;width:100vw;top:0;left:0;z-index:98" @click="tagsDiv=false"></div>
    <div class="messages-page">
        <div class="d-flex w-100 msgs-box" :class="{ 'mobile-convo-open': chatProps.active !== null }">
            <div class="chats-side">
                <div class="chats-head p-2">
                    <div class="position-relative">
                        <input type="text" class="w-100 ps-4" placeholder="Search Messages" v-model="search" />
                        <img src="/cms-assets/images/navbar-topbar/search.png" alt="search-icon"
                            class="img-fluid position-absolute"/>
                    </div>
                    <div class="mt-2">
                        <select v-model="messageOpt" name="" id="">
                            <option selected value="active">Active Messages</option>
                            <option value="archived">Archived Messages</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <button @click="newChatCompVisib = true" class="prim_btn py-1 h7 w-100 brds-1">New</button>
                    </div>
                </div>
                <div class="chats-list my-3">
                    <div v-if="chatProps.convos.length==0" class="mt-3 text-center">
                        <p>No Chat Yet</p>
                    </div>
                    <div v-for="chatSingle in filteredMessages" :key="chatSingle.id">
                        <ChatItem :chatData="chatSingle"/>
                    </div>
                </div>
            </div>
            <div class="convo-side">
                <div v-if="chatProps.active===null" class="h-100 desktop-empty-state" style="overflow-y:auto">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="px-3">
                            <img src="/cms-assets/images/no-message.png" alt="" class="img-fluid d-block mx-auto" style="max-height:250px">
                            <div class="mt-3 text-center">
                                <h5 class="mb-2"><strong>Select a message thread to view</strong></h5>
                                <p class="mb-0 text-muted">Stay connected with your clients and fellow team members with one-to-one or group private messages</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="chatProps.active!==null" class="h-100 d-flex flex-column">
                    <div class="convo-head p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center flex-grow-1 min-w-0">
                            <button type="button" class="mobile-back-btn trans_btn me-1" @click="closeMobileConvo" aria-label="Back to message list">
                                <i class="fa-solid fa-arrow-left"></i>
                            </button>
                            <button type="button" class="trans_btn px-2 align-self-center flex-shrink-0" @click="openClientProfile" :aria-label="'View profile for ' + chatProps.active.user_name">
                                <img :src="chatProps.active.image" alt="" class="rounded-circle" style="width:40px;height:40px"/>
                            </button>
                            <div class="min-w-0">
                                <div>
                                    <button type="button" class="trans_btn h7 mb-0 p-0 text-capitalize text-truncate d-inline-block" style="max-width:100%" @click="openClientProfile"><strong>{{chatProps.active.user_name}}</strong></button>
                                </div>
                                <div class="convo-meta">
                                    <span class="package-chip">{{ chatProps.active.package_label || chatProps.active.sub || 'No package' }}</span>
                                    <span class="reply-chip" :class="{ 'can-reply': chatProps.active.can_reply, 'no-reply': !chatProps.active.can_reply }">
                                        {{ chatProps.active.can_reply_label || (chatProps.active.can_reply ? 'Can reply' : 'Cannot reply') }}
                                    </span>
                                    <button type="button" class="trans_btn h8 mb-0 p-0 ms-1" style="color:#f2a18c" @click="tagsDiv=!tagsDiv">View Tags</button>
                                    <div v-if="tagsDiv" class="tsl brds-2 position-absolute p-2 tags-dropdown">
                                        <p v-if="clientsTags.names.length>0" v-for="(item, index) in clientsTags.names" :key="index" class="px-2 float-start m-1" style="background-color:#F2A18C;border-radius:20px;font-size:13px;">{{item}}</p>
                                        <p v-else class="mb-0">No tags assigned</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="convo-content flex-grow-1">
                        <div class="d-flex flex-column justify-content-end h-100">
                            <div id="messageDiv" class="px-3 px-md-4 pb-4 message-scroll" ref="scrollable">
                                <MsgItem v-for="msg in chatProps.messages" :key="msg.id" :msgdet="msg" :logInDetails="logInProps"/>
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
</template>
<script>
import config from "../config";
import ChatItem from "../components/messages/chat-item.vue";
import MsgItem from "../components/messages/msg-item.vue";
import NewChat from '../components/messages/newChat.vue';
import ClientPopup from '../components/clients/clientPopup.vue';
import Inform from '../components/inform.vue';
import Confirm from '../components/confirm.vue';
import GetInput from '../components/getInput.vue';
import Loader from '../components/loader.vue';
import Preview from '../components/messages/preview.vue';
import VoiceSendTargetModal from '../components/messages/VoiceSendTargetModal.vue';
import ForwardMessageModal from '../components/messages/ForwardMessageModal.vue';
import EmojiPicker from '../components/messages/EmojiPicker.vue';
import axios from "axios";

//  Vue.use(AudioRecorder);
export default {
    components: { ClientPopup, ChatItem, MsgItem, Inform, Confirm, NewChat, Loader, Preview, GetInput, EmojiPicker, VoiceSendTargetModal, ForwardMessageModal },
    emits: ['hideBarsEvent', 'showBarsEvent', 'adminCheckEvent', 'checkWindowEvent', 'getConvosEvent', 'activeConvoEvent', 'getMessagesEvent', 'activeGroupEvent', 'getGroupsEvent', 'getGroupMessagesEvent'],
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
            newChatCompVisib: false,
            firstMsgVisi: false,
            firstMsgTitle: '',
            firstMsgDetail: '',
            firstMsgTo: '',
            previewOn: false,
            previewFile: null,
            previewType: null,
            informModal: false,
            confirmModal: false,
            modalTitle: '',
            modalDetail: '',
            pageLoading: false,
            loadingText: "Making Chat Ready",
            search: "",
            messageOpt: 'active',
            tagsDiv: false,
            clientsTags: { names: [] },
            profileClientId: null,
            voiceTargetModal: false,
            voiceSendTarget: null,
            forwardModal: false,
            forwardMessageData: null,
        };
    },
    mounted() {
        this.$emit("adminCheckEvent");
        this.$emit('getConvosEvent', 1);
    },
    computed: {
        filteredMessages() {
            return this.filteredMessagesoption.filter((chatData) => {
                return chatData.user_name.toLowerCase().includes(this.search.toLowerCase());
            });
        },
        filteredMessagesoption() {
            return this.chatProps.convos.filter((chatData) => {
                return chatData.status.toLowerCase().includes(this.messageOpt.toLowerCase());
            });
        },
    },
    beforeUnmount() {
        this.chatProps.active = null;
    },
    methods: {
        openClientProfile() {
            this.tagsDiv = false;
            this.profileClientId = this.chatProps.active?.user_id || null;
        },
        toogleEmojiPicker() {
            this.emojiPicker = !this.emojiPicker;
        },
        updateScroll() {
            setTimeout(() => {
                let element = document.getElementById("messageDiv");
                element.scrollTop = element.scrollHeight;
            }, 100)
        },
        openChat(chat) {
            this.tagsDiv = false;
            this.clientsTags = { names: [] };
            this.$emit('activeConvoEvent', chat);
            axios.get(config.baseApiUrl + 'client-tags/' + chat.user_id, this.apiConfig).then(res => {
                if (res.data.status) {
                    if (this.chatProps.active?.user_id === chat.user_id) {
                        this.clientsTags = { names: res.data.data?.names || [] };
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
        closeMobileConvo() {
            this.tagsDiv = false;
            this.emojiPicker = false;
            this.chatProps.active = null;
        },
        sendFirstMessage(msgToSend) {
            const postData = {
                chat_id: this.chatProps.active.id,
                content: msgToSend
            }
            axios.post(config.baseApiUrl + 'send-text-message', postData, this.apiConfig).then(res => {
                if (res.data.status)
                    this.$emit('getMessagesEvent', res.data.sent_msg);
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
        sendTextMessage() {
            this.newMessage = this.newMessage.trim();
            if (this.newMessage == null || this.newMessage == '')
                return;
            const postData = {
                chat_id: this.chatProps.active.id,
                content: this.newMessage
            }
            this.newMessage = '';
            axios.post(config.baseApiUrl + 'send-text-message', postData, this.apiConfig).then(res => {
                if (res.data.status)
                    this.$emit('getMessagesEvent', res.data.sent_msg);
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
            let endpoint = 'send-file-message';
            if (target?.type === 'group') {
                endpoint = 'send-group-file-message';
                fd.append('group_id', target.groupId);
            } else if (target?.type === 'program') {
                endpoint = 'multiple-users-file-message';
                target.userIds.forEach((id) => fd.append('user_ids[]', id));
            } else {
                const chatId = target?.chatId || (this.chatProps.active ? this.chatProps.active.id : null);
                if (!chatId) {
                    this.modalTitle = 'Error';
                    this.modalDetail = 'Select a client chat before sending a file.';
                    this.informModal = true;
                    return;
                }
                fd.append('chat_id', chatId);
            }

            axios.post(config.baseApiUrl + endpoint, fd, apiCon).then(res => {
                this.voiceSendTarget = null;
                if (res.data.status) {
                    if (endpoint === 'send-file-message') {
                        this.$emit('getMessagesEvent', res.data.sent_msg);
                    } else if (endpoint === 'send-group-file-message') {
                        this.$emit('getGroupMessagesEvent', res.data.sent_msg);
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
        startNewChat(name, chat) {
            this.newChatCompVisib = false;
            this.firstMsgTitle = "Chat with " + name;
            this.firstMsgDetail = "Say Hello! or something";
            this.firstMsgVisi = true;
            this.$emit('activeConvoEvent', chat);
        },
        deleteChat(id) {
            this.chatProps.active = null;
            this.pageLoading = true;
            axios.get(config.baseApiUrl + 'delete-chat/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$emit('getConvosEvent', 1);
                    this.modalTitle = 'Done!';
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
        deleteMessage(id) {
            this.pageLoading = true;
            this.loadingText = 'Deleteing';
            axios.get(config.baseApiUrl + 'delete-message/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.modalTitle = 'Done!';
                    this.$emit('getConvosEvent');
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
        archiveChat(id) {
            this.chatProps.active = null;
            this.pageLoading = false;
            this.loadingText = 'Archiving';
            axios.get(config.baseApiUrl + 'archive-chat/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$emit('getConvosEvent', 1);
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
        unarchiveChat(id) {
            this.chatProps.active = null;
            this.pageLoading = false;
            this.loadingText = 'Unarchiving';
            axios.get(config.baseApiUrl + 'unarchive-chat/' + id, this.apiConfig).then(res => {
                this.pageLoading = false;
                if (res.data.status) {
                    this.$emit('getConvosEvent', 1);
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
        inputResponse(msg) {
            this.firstMsgVisi = false;
            if (msg !== null) {
                this.sendFirstMessage(msg);
            }
            else if (msg == null || msg == '') {
                this.$emit('getConvosEvent', 1);
                this.chatProps.active = null;
            }
        },
        acknowledged() {
            this.informModal = false;
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
            if (result.target_type === 'chat') {
                this.$emit('getConvosEvent', 1);
            } else {
                this.$emit('getGroupsEvent', 1);
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
        }
    },
};
</script>
<style scoped>
.messages-page {
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

.convo-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    position: relative;
}

.package-chip,
.reply-chip {
    display: inline-block;
    border-radius: 999px;
    font-size: 11px;
    line-height: 1.2;
    padding: 3px 8px;
}

.package-chip {
    background: #FCEAE5;
    color: #8A3B2A;
}

.reply-chip.can-reply {
    background: #E5F8EC;
    color: #147333;
}

.reply-chip.no-reply {
    background: #F8E5E5;
    color: #A11B1B;
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

.tags-dropdown {
    max-height: 100px;
    width: 200px;
    max-width: calc(100vw - 48px);
    background-color: white;
    z-index: 99;
    overflow-y: auto;
    top: 20px;
    left: 0;
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
