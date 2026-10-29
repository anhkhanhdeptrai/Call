<script setup>
import { ref } from "vue";
import {
  StringeeClient,
  StringeeCall
} from "stringee";

// =====================================================
// STATE
// =====================================================

const username = ref("");
const friendUsername = ref("");

const loggedIn = ref(false);
const loading = ref(false);

const callStatus = ref("Chưa kết nối");

const hasIncomingCall = ref(false);
const isCalling = ref(false);
const isVideoCall = ref(false);

const callerName = ref("");

const localVideo = ref(null);
const remoteVideo = ref(null);

const debugText = ref("");

let call = null;

// =====================================================
// STRINGEE CLIENT
// =====================================================

const stringeeClient = new StringeeClient();

// =====================================================
// DEBUG
// =====================================================

function debug(title, data) {
  console.log("================================");
  console.log(title);
  console.log(data);
  console.log("JSON:", JSON.stringify(data));
  console.log("================================");

  debugText.value =
    title +
    "\n\n" +
    JSON.stringify(
      data,
      null,
      2
    );
}

// =====================================================
// RESET CALL
// =====================================================

function resetCall() {
  loading.value = false;
  isCalling.value = false;
  hasIncomingCall.value = false;

  callerName.value = "";

  if (localVideo.value) {
    localVideo.value.srcObject = null;
  }

  if (remoteVideo.value) {
    remoteVideo.value.srcObject = null;
  }

  call = null;
}

// =====================================================
// CALL EVENTS
// =====================================================

function settingCallEvent(callObject) {
  if (!callObject) {
    console.error(
      "Call object is null"
    );
    return;
  }

  console.log(
    "Setting call events:",
    callObject
  );

  // LOCAL STREAM
  callObject.on(
    "addlocalstream",
    (stream) => {
      console.log(
        "addlocalstream:",
        stream
      );

      if (localVideo.value) {
        localVideo.value.srcObject =
          null;

        localVideo.value.srcObject =
          stream;

        localVideo.value
          .play()
          .catch(() => {});
      }
    }
  );

  // REMOTE STREAM
  callObject.on(
    "addremotestream",
    (stream) => {
      console.log(
        "addremotestream:",
        stream
      );

      if (remoteVideo.value) {
        remoteVideo.value.srcObject =
          null;

        remoteVideo.value.srcObject =
          stream;

        remoteVideo.value
          .play()
          .catch(() => {});
      }
    }
  );

  // SIGNALING
  callObject.on(
    "signalingstate",
    (state) => {
      console.log(
        "signalingstate:",
        state
      );

      debug(
        "SIGNALING STATE",
        state
      );

      if (state?.reason) {
        callStatus.value =
          state.reason;
      }
    }
  );

  // MEDIA
  callObject.on(
    "mediastate",
    (state) => {
      console.log(
        "mediastate:",
        state
      );
    }
  );

  // INFO
  callObject.on(
    "info",
    (info) => {
      console.log(
        "info:",
        info
      );
    }
  );

  // ERROR
  callObject.on(
    "error",
    (error) => {
      console.error(
        "CALL ERROR:",
        error
      );

      debug(
        "CALL ERROR",
        error
      );

      loading.value = false;

      callStatus.value =
        "CALL ERROR: " +
        (
          error?.message ||
          error?.reason ||
          JSON.stringify(error)
        );
    }
  );
}

// =====================================================
// CONNECT
// =====================================================

stringeeClient.on(
  "connect",
  () => {
    console.log(
      "Stringee connected"
    );

    callStatus.value =
      "Đã kết nối Stringee Server";
  }
);

// =====================================================
// AUTHEN
// =====================================================

stringeeClient.on(
  "authen",
  (res) => {
    debug(
      "AUTHEN RESULT",
      res
    );

    loading.value = false;

    if (
      res &&
      res.r === 0
    ) {
      loggedIn.value = true;

      callStatus.value =
        "Đăng nhập thành công";

    } else {
      loggedIn.value = false;

      callStatus.value =
        "AUTHEN ERROR: " +
        (
          res?.message ||
          "Unknown error"
        );

      alert(
        "Đăng nhập thất bại\n\n" +
        "Code: " +
        res?.r +
        "\n" +
        "Message: " +
        (
          res?.message ||
          "Unknown error"
        )
      );
    }
  }
);

// =====================================================
// INCOMING CALL
// =====================================================

stringeeClient.on(
  "incomingcall",
  (incomingCall) => {

    console.log(
      "========== INCOMING CALL =========="
    );

    console.log(
      "incomingCall:",
      incomingCall
    );

    console.log(
      "fromNumber:",
      incomingCall.fromNumber
    );

    console.log(
      "toNumber:",
      incomingCall.toNumber
    );

    console.log(
      "isVideoCall:",
      incomingCall.isVideoCall
    );

    debug(
      "INCOMING CALL",
      {
        fromNumber:
          incomingCall.fromNumber,

        toNumber:
          incomingCall.toNumber,

        isVideoCall:
          incomingCall.isVideoCall,

        call: incomingCall
      }
    );

    /*
     * QUAN TRỌNG:
     *
     * Phải giữ nguyên object
     * incomingCall này.
     */

    call = incomingCall;

    settingCallEvent(
      call
    );

    callerName.value =
      incomingCall.fromNumber ||
      "Unknown";

    isVideoCall.value =
      !!incomingCall.isVideoCall;

    hasIncomingCall.value =
      true;

    isCalling.value =
      false;

    loading.value =
      false;

    callStatus.value =
      "Có cuộc gọi đến từ " +
      callerName.value;
  }
);

// =====================================================
// INCOMING CALL 2
// =====================================================

stringeeClient.on(
  "incomingcall2",
  (incomingCall) => {

    console.log(
      "========== INCOMING CALL 2 =========="
    );

    console.log(
      incomingCall
    );

    /*
     * Chỉ xử lý nếu SDK thực sự
     * gửi incomingcall2.
     */

    if (!incomingCall) {
      return;
    }

    call = incomingCall;

    settingCallEvent(
      call
    );

    callerName.value =
      incomingCall.fromNumber ||
      "Unknown";

    isVideoCall.value =
      !!incomingCall.isVideoCall;

    hasIncomingCall.value =
      true;

    isCalling.value =
      false;

    loading.value =
      false;

    callStatus.value =
      "Có cuộc gọi đến từ " +
      callerName.value;
  }
);

// =====================================================
// LOGIN
// =====================================================

async function onLogin() {

  if (
    !username.value.trim()
  ) {
    alert(
      "Nhập Stringee User ID"
    );

    return;
  }

  const endpoint =
    import.meta.env
      .VITE_JWT_ENDPOINT;

  if (!endpoint) {
    alert(
      "Thiếu VITE_JWT_ENDPOINT"
    );

    return;
  }

  loading.value = true;

  callStatus.value =
    "Đang lấy token...";

  try {

    const separator =
      endpoint.includes("?")
        ? "&"
        : "?";

    const url =
      endpoint +
      separator +
      "u=" +
      encodeURIComponent(
        username.value.trim()
      );

    console.log(
      "TOKEN URL:",
      url
    );

    const response =
      await fetch(url);

    if (!response.ok) {
      throw new Error(
        "HTTP " +
        response.status
      );
    }

    const data =
      await response.json();

    debug(
      "TOKEN RESPONSE",
      data
    );

    if (
      !data.access_token
    ) {
      throw new Error(
        "Không nhận được access_token"
      );
    }

    callStatus.value =
      "Đang kết nối Stringee...";

    stringeeClient.connect(
      data.access_token
    );

  } catch (error) {

    console.error(
      "LOGIN ERROR:",
      error
    );

    loading.value = false;

    callStatus.value =
      "LOGIN ERROR: " +
      error.message;

    alert(
      "Login error\n\n" +
      error.message
    );
  }
}

// =====================================================
// MAKE CALL
// =====================================================

function onCall(
  video
) {

  if (!loggedIn.value) {
    alert(
      "Chưa đăng nhập"
    );

    return;
  }

  if (loading.value) {
    return;
  }

  if (isCalling.value) {
    return;
  }

  if (
    !friendUsername.value.trim()
  ) {
    alert(
      "Nhập User ID người nhận"
    );

    return;
  }

  const from =
    username.value.trim();

  const to =
    friendUsername.value.trim();

  if (from === to) {
    alert(
      "Không thể gọi chính mình"
    );

    return;
  }

  console.log(
    "========== MAKE CALL =========="
  );

  console.log(
    "FROM:",
    from
  );

  console.log(
    "TO:",
    to
  );

  console.log(
    "VIDEO:",
    video
  );

  loading.value = true;

  isVideoCall.value =
    video;

  callerName.value =
    to;

  callStatus.value =
    video
      ? "Đang gọi video..."
      : "Đang gọi thoại...";

  try {

    /*
     * Stringee Web SDK:
     *
     * true  = video
     * false = voice
     */

    call =
      new StringeeCall(
        stringeeClient,
        from,
        to,
        video
      );

    settingCallEvent(
      call
    );

    call.makeCall(
      (res) => {

        debug(
          "MAKE CALL RESULT",
          res
        );

        if (
          res &&
          res.r === 0
        ) {

          callStatus.value =
            "Đang chờ " +
            to +
            " nhận cuộc gọi...";

          /*
           * Chưa set isCalling=true
           * vì bên kia chưa answer.
           */

          loading.value = true;

        } else {

          loading.value =
            false;

          isCalling.value =
            false;

          callStatus.value =
            "MAKE CALL ERROR: " +
            (
              res?.message ||
              "Unknown error"
            );

          alert(
            "MAKE CALL ERROR\n\n" +
            "Code: " +
            res?.r +
            "\n\n" +
            "Message:\n" +
            (
              res?.message ||
              "Unknown error"
            )
          );
        }
      }
    );

  } catch (error) {

    console.error(
      "MAKE CALL EXCEPTION:",
      error
    );

    loading.value = false;

    callStatus.value =
      "MAKE CALL EXCEPTION: " +
      error.message;

    alert(
      "Make call exception\n\n" +
      error.message
    );
  }
}

// =====================================================
// ACCEPT CALL
// =====================================================

function acceptCall() {

  console.log(
    "========== ACCEPT =========="
  );

  console.log(
    "CALL:",
    call
  );

  if (!call) {

    alert(
      "Không có call object"
    );

    return;
  }

  loading.value = true;

  callStatus.value =
    "Đang trả lời...";

  try {

    /*
     * Đây là flow chính thức:
     *
     * incomingcall
     *      ↓
     * call = incomingcall
     *      ↓
     * call.answer()
     */

    call.answer(
      (res) => {

        console.log(
          "========== ANSWER RESULT =========="
        );

        console.log(
          "FULL RESULT:",
          res
        );

        console.log(
          "r:",
          res?.r
        );

        console.log(
          "message:",
          res?.message
        );

        console.log(
          "reason:",
          res?.reason
        );

        console.log(
          "JSON:",
          JSON.stringify(
            res
          )
        );

        debug(
          "ANSWER RESULT",
          res
        );

        if (
          res &&
          res.r === 0
        ) {

          loading.value =
            false;

          hasIncomingCall.value =
            false;

          isCalling.value =
            true;

          callStatus.value =
            "Đã trả lời cuộc gọi";

        } else {

          loading.value =
            false;

          callStatus.value =
            "ANSWER ERROR: " +
            (
              res?.message ||
              res?.reason ||
              "Unknown error"
            );

          alert(
            "REQUEST ANSWER ERROR\n\n" +
            "Code: " +
            res?.r +
            "\n\n" +
            "Message:\n" +
            (
              res?.message ||
              "Không có message"
            ) +
            "\n\n" +
            "Reason:\n" +
            (
              res?.reason ||
              "Không có reason"
            )
          );
        }
      }
    );

  } catch (error) {

    console.error(
      "ANSWER EXCEPTION:",
      error
    );

    loading.value = false;

    callStatus.value =
      "ANSWER EXCEPTION: " +
      error.message;

    alert(
      "Answer exception\n\n" +
      error.message
    );
  }
}

// =====================================================
// REJECT
// =====================================================

function rejectCall() {

  if (!call) {
    return;
  }

  console.log(
    "REJECT CALL"
  );

  call.reject(
    (res) => {

      debug(
        "REJECT RESULT",
        res
      );

      resetCall();

      callStatus.value =
        "Đã từ chối cuộc gọi";
    }
  );
}

// =====================================================
// HANGUP
// =====================================================

function hangupCall() {

  if (!call) {
    resetCall();

    return;
  }

  console.log(
    "HANGUP CALL"
  );

  call.hangup(
    (res) => {

      debug(
        "HANGUP RESULT",
        res
      );

      resetCall();

      callStatus.value =
        "Đã kết thúc cuộc gọi";
    }
  );
}
</script>

<template>

  <div
    class="container"
    style="
      max-width: 700px;
      margin: 30px auto;
      font-family: Arial;
    "
  >

    <h2>
      Stringee Voice / Video Call
    </h2>

    <!-- STATUS -->

    <div
      style="
        padding: 15px;
        margin: 15px 0;
        background: #eee;
        border-radius: 8px;
      "
    >

      <b>
        Status:
      </b>

      <div>
        {{ callStatus }}
      </div>

    </div>

    <!-- LOGIN -->

    <div
      v-if="!loggedIn"
    >

      <input
        v-model="username"
        placeholder="User ID: user_a"
        style="
          width: 100%;
          padding: 12px;
          margin-bottom: 10px;
          box-sizing: border-box;
        "
      />

      <button
        @click="onLogin"
        :disabled="loading"
        style="
          padding: 12px 20px;
        "
      >

        {{
          loading
            ? "Đang đăng nhập..."
            : "Đăng nhập"
        }}

      </button>

    </div>

    <!-- CALL -->

    <div
      v-else
    >

      <p>
        Đăng nhập:
        <b>{{ username }}</b>
      </p>

      <input
        v-model="friendUsername"
        placeholder="User ID người nhận: user_b"
        :disabled="
          loading ||
          isCalling
        "
        style="
          width: 100%;
          padding: 12px;
          margin-bottom: 10px;
          box-sizing: border-box;
        "
      />

      <div>

        <button
          @click="onCall(false)"
          :disabled="
            loading ||
            isCalling
          "
          style="
            padding: 12px 20px;
            margin-right: 10px;
          "
        >
          📞 Gọi thoại
        </button>

        <button
          @click="onCall(true)"
          :disabled="
            loading ||
            isCalling
          "
          style="
            padding: 12px 20px;
          "
        >
          🎥 Gọi video
        </button>

      </div>

    </div>

    <!-- INCOMING -->

    <div
      v-if="hasIncomingCall"
      style="
        margin-top: 20px;
        padding: 20px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
      "
    >

      <h3>
        📞 Cuộc gọi đến
      </h3>

      <p>
        Từ:
        <b>
          {{ callerName }}
        </b>
      </p>

      <p>
        Loại:
        <b>
          {{
            isVideoCall
              ? "Video"
              : "Voice"
          }}
        </b>
      </p>

      <button
        @click="acceptCall"
        :disabled="loading"
        style="
          padding: 12px 20px;
          margin-right: 10px;
          background: #198754;
          color: white;
          border: 0;
          border-radius: 5px;
        "
      >
        ☎️ Trả lời
      </button>

      <button
        @click="rejectCall"
        :disabled="loading"
        style="
          padding: 12px 20px;
          background: #dc3545;
          color: white;
          border: 0;
          border-radius: 5px;
        "
      >
        ❌ Từ chối
      </button>

    </div>

    <!-- ACTIVE CALL -->

    <div
      v-if="isCalling"
      style="
        margin-top: 20px;
        padding: 20px;
        background: #d1e7dd;
        border-radius: 8px;
      "
    >

      <h3>
        🟢 Đang gọi
      </h3>

      <p>
        Với:
        <b>
          {{ callerName }}
        </b>
      </p>

      <button
        @click="hangupCall"
        style="
          padding: 12px 20px;
          background: #dc3545;
          color: white;
          border: 0;
          border-radius: 5px;
        "
      >
        📵 Kết thúc
      </button>

    </div>

    <!-- VIDEO -->

    <div
      v-if="
        isCalling &&
        isVideoCall
      "
      style="
        margin-top: 20px;
      "
    >

      <h3>
        🎥 Video
      </h3>

      <div
        style="
          display: flex;
          gap: 10px;
        "
      >

        <div
          style="
            width: 50%;
          "
        >

          <p>
            Camera của bạn
          </p>

          <video
            ref="localVideo"
            autoplay
            muted
            playsinline
            style="
              width: 100%;
              background: black;
            "
          ></video>

        </div>

        <div
          style="
            width: 50%;
          "
        >

          <p>
            Camera người nhận
          </p>

          <video
            ref="remoteVideo"
            autoplay
            playsinline
            style="
              width: 100%;
              background: black;
            "
          ></video>

        </div>

      </div>

    </div>

    <!-- DEBUG -->

    <details
      style="
        margin-top: 30px;
      "
    >

      <summary>
        Debug
      </summary>

      <pre
        style="
          background: #111;
          color: #0f0;
          padding: 15px;
          overflow: auto;
          white-space: pre-wrap;
        "
      >User: {{ username }}

Target: {{ friendUsername }}

Logged: {{ loggedIn }}

Calling: {{ isCalling }}

Incoming: {{ hasIncomingCall }}

Video: {{ isVideoCall }}

{{ debugText }}</pre>

    </details>

  </div>

</template>
