/**
 * Account > Messages: wires the shared chat (composer, attachments, optimistic send, live replies)
 * to the page. All behaviour lives in ../chat/chat.js, the same module the landing widget and the
 * admin Messages page use; this file only starts it on the account card and scrolls to the newest
 * message.
 *
 * Back-end contract: POST `user.messages.send` (multipart FormData `body`, `attachments[]`)
 * -> 200 {"ok": true}; GET `user.messages.updates` -> {"unread", "messages"}; POST
 * `user.messages.read` marks the thread read.
 */

import { initChat, initChatStream, scrollLog } from "../chat/chat.js";

function initMessages() {
    const root = document.querySelector("[data-account-chat]");
    if (!root) return;

    initChat(root);
    initChatStream(root);

    const log = root.querySelector("[data-chat-log]");
    if (log) scrollLog(log);
}

document.addEventListener("DOMContentLoaded", initMessages);