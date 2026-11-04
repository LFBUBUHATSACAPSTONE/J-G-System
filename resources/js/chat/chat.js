// Shared chat behaviour (admin panel + user widget): composer, attachments, optimistic send.
// Front end only: POSTs to the form's action (a named route) and expects 200 {ok:true} or
// 422 {message, errors}. Same fetch header pattern as the auth and booking forms.
import { getCsrfToken } from "../auth/csrf.js";

const el = (tag, cls, text) => {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
};
// Inline SVG (the user pages load no icon font). Constant markup only, never user text.
const ICONS = {
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
};
const icon = (name) => {
    const t = document.createElement("template");
    t.innerHTML = `<svg class="chat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">${ICONS[name]}</svg>`;
    return t.content.firstChild;
};
const fmtSize = (b) =>
    b < 1048576
        ? `${Math.max(1, Math.round(b / 1024))} KB`
        : `${(b / 1048576).toFixed(1)} MB`;
const timeLabel = () =>
    new Date().toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit",
    });
const dayKey = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

export const scrollLog = (log) => {
    log.scrollTop = log.scrollHeight;
};

function attachmentNode(file) {
    const url = URL.createObjectURL(file);
    const isImage = file.type.startsWith("image/");
    const a = el("a", `chat-attach${isImage ? " chat-attach--image" : ""}`);
    a.href = url;
    a.target = "_blank";
    a.rel = "noopener";
    if (isImage) {
        const img = el("img");
        img.src = url;
        img.alt = file.name;
        a.append(img);
    } else {
        a.append(
            icon("file"),
            el("span", "chat-attach__name", file.name),
            el("span", "chat-attach__size", fmtSize(file.size)),
        );
    }
    return a;
}

// Text goes in with textContent only, never innerHTML.
function appendMessage(log, body, files) {
    const key = dayKey();
    const days = log.querySelectorAll(".chat-day");
    if (!days.length || days[days.length - 1].dataset.day !== key) {
        const d = el("p", "chat-day", "Today");
        d.dataset.day = key;
        log.querySelector(".chat-empty")?.remove();
        log.append(d);
    }
    const msg = el("div", "chat-msg chat-msg--out");
    const bubble = el("div", "chat-msg__bubble");
    bubble.append(el("span", "visually-hidden", "You: "));
    if (body) bubble.append(el("p", "chat-msg__text", body));
    files.forEach((f) => bubble.append(attachmentNode(f)));
    const time = el("time", "chat-msg__time", "Sending…");
    msg.append(bubble, time);
    log.append(msg);
    scrollLog(log);
    return { msg, time };
}

export function initChat(root) {
    const form = root.querySelector("[data-chat-form]");
    const log = root.querySelector("[data-chat-log]");
    if (!form || !log) return;

    const input = form.querySelector("[data-chat-input]");
    const picker = form.querySelector("[data-chat-picker]");
    const list = form.querySelector("[data-chat-files]");
    const error = form.querySelector("[data-chat-error]");
    const max = {
        files: +form.dataset.maxFiles,
        bytes: +form.dataset.maxBytes,
        types: form.dataset.accept.split(","),
    };
    let files = [];

    const setError = (text) => {
        error.textContent = text || "";
        error.hidden = !text;
    };
    const autosize = () => {
        input.style.height = "auto";
        input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
    };

    function renderFiles() {
        list.replaceChildren(
            ...files.map((f, i) => {
                const li = el("li", null, `${f.name} (${fmtSize(f.size)})`);
                const rm = el("button");
                rm.type = "button";
                rm.setAttribute("aria-label", `Remove ${f.name}`);
                rm.append(icon("x"));
                rm.addEventListener("click", () => {
                    files.splice(i, 1);
                    renderFiles();
                    input.focus();
                });
                li.append(rm);
                return li;
            }),
        );
        list.hidden = !files.length;
    }

    picker.addEventListener("change", () => {
        setError("");
        for (const f of picker.files) {
            if (files.length >= max.files) {
                setError(`You can attach up to ${max.files} files.`);
                break;
            }
            if (!max.types.includes(f.type)) {
                setError(
                    `${f.name} is not supported. Use JPG, PNG, WEBP or PDF.`,
                );
                continue;
            }
            if (f.size > max.bytes) {
                setError(`${f.name} is larger than ${fmtSize(max.bytes)}.`);
                continue;
            }
            files.push(f);
        }
        picker.value = "";
        renderFiles();
    });

    input.addEventListener("input", autosize);
    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter" && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            form.requestSubmit();
        }
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body && !files.length) return;

        setError("");
        const sent = files;
        files = [];
        renderFiles();
        input.value = "";
        autosize();

        const { msg, time } = appendMessage(log, body, sent);
        const data = new FormData();
        if (body) data.append("body", body);
        sent.forEach((f) => data.append("attachments[]", f));

        try {
            const res = await fetch(form.action, {
                method: "POST",
                headers: {
                    Accept: "application/json",
                    "X-CSRF-TOKEN": getCsrfToken(form),
                },
                body: data,
            });
            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                throw Object.assign(new Error(json.message || ""), {
                    status: res.status,
                });
            }
            time.textContent = timeLabel();
            root.dispatchEvent(
                new CustomEvent("chat:sent", {
                    bubbles: true,
                    detail: { body, files: sent.length },
                }),
            );
        } catch (err) {
            // Nothing is lost: take the bubble back and restore the draft.
            msg.remove();
            input.value = body;
            files = sent;
            renderFiles();
            autosize();
            setError(
                err.status === 422 && err.message
                    ? err.message
                    : "Message not sent. Check your connection and try again.",
            );
        }
    });
}
