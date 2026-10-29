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

let call = null;

// =====================================================
// STRINGEE CLIENT
// =====================================================

const stringeeClient = new StringeeClient();

// =====================================================
// HELPER
// =====================================================

function showResult(title, res) {
  console.log("=================================");
  console.log(title);
  console.log("FULL RESULT:", res);
  console.log("CODE r:", res?.r);
  console.log("MESSAGE:", res?.message);
  console.log("REASON:", res?.reason);
  console.log("JSON:", JSON.stringify(res));
  console.log("=================================");

  const code =
    res?.r !== undefined
      ? res.r
      : "unknown";

  const message =
    res?.message ||
    res?.reason ||
    "Không có message";

  callStatus.value =
    `${title}: code=${code}, message=${message}`;
}

// =====================================================
// CALL EVENT
// =====================================================

function settingCallEvent(callObject) {
  if (!callObject) {
    console.error(
      "CALL OBJECT KHÔNG TỒN TẠI"
    );
    return;
  }

  console.log(
    "Đăng ký event cho call:",
    callObject
  );

  // ---------------------------------------------------
  // LOCAL STREAM
  // ---------------------------------------------------

  callObject.on(
    "addlocalstream",
    (stream) => {
      console.log(
        "ADD LOCAL STREAM:",
        stream
      );

      if (localVideo.value) {
        localVideo.value.srcObject =
          stream;

        localVideo.value.play()
          .catch(() => {});
      }
    }
  );

  // ---------------------------------------------------
  // REMOTE STREAM
  // ---------------------------------------------------

  callObject.on(
    "addremotestream",
    (stream) => {
      console.log(
        "ADD REMOTE STREAM:",
        stream
      );

      if (remoteVideo.value) {
        remoteVideo.value.srcObject =
          stream;

        remoteVideo.value.play()
          .catch(() => {});
      }
    }
  );

  // ---------------------------------------------------
  // SIGNALING STATE
  // ---------------------------------------------------

  callObject.on(
    "signalingstate",
    (state) => {
      console.log(
        "SIGNALING STATE:",
        state
      );

      console.log(
        "SIGNALING JSON:",
        JSON.stringify(state)
      );

      if (state?.reason) {
        callStatus.value =
          state.reason;
      }

      /*
       * Một số trạng thái Stringee:
       * code có thể thay đổi tùy SDK.
       *
       * Vì vậy không phụ thuộc hoàn toàn
       * vào một code duy nhất.
       */

      const reason =
        String(
          state?.reason ||
          state?.message ||
          ""
        ).toLowerCase();

      if (
        reason.includes("accept") ||
        reason.includes("answer") ||
        reason.includes("connected")
      ) {
        loading.value = false;
        isCalling.value = true;
        hasIncomingCall.value = false;
      }

      if (
        reason.includes("hangup") ||
        reason.includes("ended") ||
        reason.includes("reject") ||
        reason.includes("busy") ||
        reason.includes("cancel")
      ) {
        resetCall();
      }
    }
  );

  // ---------------------------------------------------
  // MEDIA STATE
  // ---------------------------------------------------

  callObject.on(
    "mediastate",
    (state) => {
      console.log(
        "MEDIA STATE:",
        state
      );
    }
  );

  // ---------------------------------------------------
  // INFO
  // ---------------------------------------------------

  callObject.on(
    "info",
    (info) => {
      console.log(
        "CALL INFO:",
        info
      );
    }
  );

  // ---------------------------------------------------
  // ERROR
  // ---------------------------------------------------

  callObject.on(
    "error",
    (error) => {
      console.error(
        "CALL ERROR:",
        error
      );

      console.error(
        "CALL ERROR JSON:",
        JSON.stringify(error)
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
// RESET CALL
// =====================================================

function resetCall() {
  console.log(
    "RESET CALL"
  );

  loading.value = false;
  isCalling.value = false;
  hasIncomingCall.value = false;

  callerName.value = "";

  if (localVideo.value) {
    localVideo.value.srcObject =
      null;
  }

  if (remoteVideo.value) {
    remoteVideo.value.srcObject =
      null;
  }

  call = null;
}

// =====================================================
// STRINGEE CONNECT
// =====================================================

stringeeClient.on(
  "connect",
  () => {
    console.log(
      "STRINGEE CONNECTED"
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
    showResult(
      "AUTHEN RESULT",
      res
    );

    loading.value = false;

    if (
      res &&
      (
        res.r === 0 ||
        res.message === "SUCCESS"
      )
    ) {
      loggedIn.value = true;

      callStatus.value =
        "Đăng nhập Stringee thành công";

      console.log(
        "USER ID:",
        username.value
      );
    } else {
      loggedIn.value = false;

      callStatus.value =
        "Đăng nhập thất bại: " +
        (
          res?.message ||
          "Unknown error"
        );

      alert(
        "Đăng nhập Stringee thất bại\n\n" +
        "Code: " +
        res?.r +
        "\n" +
        "Message: " +
        (
          res?.message ||
          "Không có message"
        )
      );
    }
  }
);

// =====================================================
// INCOMING CALL
// =====================================================

function handleIncomingCall(
  incomingCall
) {
  console.log(
    "================================="
  );

  console.log(
    "INCOMING CALL"
  );

  console.log(
    "CALL:",
    incomingCall
  );

  console.log(
    "================================="
  );

  call =
    incomingCall;

  settingCallEvent(
    incomingCall
  );

  hasIncomingCall.value =
    true;

  isCalling.value =
    false;

  loading.value =
    false;

  callerName.value =
    incomingCall.fromNumber ||
    incomingCall.from ||
    "Người gọi";

  isVideoCall.value =
    !!(
      incomingCall.isVideoCall
    );

  callStatus.value =
    "Có cuộc gọi đến từ " +
    callerName.value;
}

// Voice incoming
stringeeClient.on(
  "incomingcall",
  handleIncomingCall
);

// Video/Call2 incoming nếu SDK phát event này
stringeeClient.on(
  "incomingcall2",
  handleIncomingCall
);

// =====================================================
// LOGIN
// =====================================================

async function onLogin() {
  if (
    !username.value.trim()
  ) {
    alert(
      "Vui lòng nhập Stringee User ID"
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
    "Đang lấy access token...";

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

    console.log(
      "TOKEN HTTP STATUS:",
      response.status
    );

    if (!response.ok) {
      throw new Error(
        `HTTP ${response.status}`
      );
    }

    const data =
      await response.json();

    console.log(
      "TOKEN RESPONSE:",
      data
    );

    if (
      !data ||
      !data.access_token
    ) {
      throw new Error(
        "Backend không trả access_token"
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
      "Lỗi đăng nhập\n\n" +
      error.message
    );
  }
}

// =====================================================
// MAKE CALL
// =====================================================

function onCall(
  video = false
) {
  if (!loggedIn.value) {
    alert(
      "Bạn chưa đăng nhập Stringee"
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
      "Vui lòng nhập User ID người nhận"
    );

    return;
  }

  const from =
    username.value.trim();

  const to =
    friendUsername.value.trim();

  if (from === to) {
    alert(
      "Không thể gọi cho chính mình"
    );

    return;
  }

  console.log(
    "================================="
  );

  console.log(
    "MAKE CALL"
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

  console.log(
    "================================="
  );

  loading.value = true;

  isVideoCall.value =
    video;

  callStatus.value =
    video
      ? "Đang gọi video..."
      : "Đang gọi thoại...";

  callerName.value =
    to;

  try {
    /*
     * StringeeCall:
     *
     * false = voice
     * true  = video
     */

    call =
      new StringeeCall(
        stringeeClient,
        from,
        to,
        video
      );

    console.log(
      "CREATED CALL:",
      call
    );

    settingCallEvent(
      call
    );

    call.makeCall(
      (res) => {
        showResult(
          "MAKE CALL RESULT",
          res
        );

        /*
         * Đây là phần quan trọng
         * để bắt lỗi CODE 18.
         */

        if (
          res &&
          res.r === 0
        ) {
          loading.value =
            true;

          isCalling.value =
            false;

          callStatus.value =
            "Đang chờ " +
            to +
            " nhận cuộc gọi...";
        } else {
          loading.value =
            false;

          isCalling.value =
            false;

          const message =
            res?.message ||
            res?.reason ||
            "Unknown error";

          callStatus.value =
            "Gọi thất bại: " +
            message;

          /*
           * Hiện đầy đủ dữ liệu lỗi
           */

          alert(
            "GỌI THẤT BẠI\n\n" +
            "Code: " +
            res?.r +
            "\n\n" +
            "Message:\n" +
            message +
            "\n\n" +
            "Xem Console để biết FULL RESULT."
          );
        }
      }
    );
  } catch (error) {
    console.error(
      "MAKE CALL EXCEPTION:",
      error
    );

    loading.value =
      false;

    isCalling.value =
      false;

    callStatus.value =
      "Exception: " +
      error.message;

    alert(
      "Lỗi tạo cuộc gọi\n\n" +
      error.message
    );
  }
}

// =====================================================
// ACCEPT CALL
// =====================================================

function acceptCall() {
  if (!call) {
    alert(
      "Không tìm thấy cuộc gọi"
    );

    return;
  }

  console.log(
    "ACCEPT CALL:",
    call
  );

  loading.value = true;

  call.answer(
    (res) => {
      showResult(
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

        alert(
          "Trả lời thất bại\n\n" +
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
}

// =====================================================
// REJECT CALL
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
      showResult(
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
      showResult(
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
  <div class="container py-4">

    <div class="row">

      <div class="col-md-8 mx-auto">

        <h2 class="mb-4">
          Stringee Voice / Video Call
        </h2>

        <!-- ========================= -->
        <!-- STATUS -->
        <!-- ========================= -->

        <div class="alert alert-info">

          <strong>
            Trạng thái:
          </strong>

          <div class="mt-1">
            {{ callStatus }}
          </div>

        </div>

        <!-- ========================= -->
        <!-- LOGIN -->
        <!-- ========================= -->

        <div
          v-if="!loggedIn"
          class="card p-3"
        >

          <h5>
            Đăng nhập
          </h5>

          <label
            class="form-label mt-2"
          >
            Stringee User ID
          </label>

          <input
            v-model="username"
            class="form-control"
            placeholder="user_a"
            autocomplete="off"
            :disabled="loading"
          />

          <button
            class="btn btn-primary mt-3"
            @click="onLogin"
            :disabled="loading"
          >
            {{
              loading
                ? "Đang đăng nhập..."
                : "Đăng nhập"
            }}
          </button>

        </div>

        <!-- ========================= -->
        <!-- CALL PANEL -->
        <!-- ========================= -->

        <div
          v-else
          class="card p-3"
        >

          <h5>
            Gọi điện
          </h5>

          <div class="mb-3">

            <label
              class="form-label"
            >
              User ID người nhận
            </label>

            <input
              v-model="friendUsername"
              class="form-control"
              placeholder="user_b"
              autocomplete="off"
              :disabled="
                loading ||
                isCalling
              "
            />

          </div>

          <div>

            <!-- VOICE -->

            <button
              class="btn btn-primary me-2"
              @click="onCall(false)"
              :disabled="
                loading ||
                isCalling
              "
            >
              📞 Gọi thoại
            </button>

            <!-- VIDEO -->

            <button
              class="btn btn-success"
              @click="onCall(true)"
              :disabled="
                loading ||
                isCalling
              "
            >
              🎥 Gọi video
            </button>

          </div>

        </div>

        <!-- ========================= -->
        <!-- INCOMING CALL -->
        <!-- ========================= -->

        <div
          v-if="hasIncomingCall"
          class="card mt-3 border-warning"
        >

          <div
            class="card-body"
          >

            <h5>
              📞 Cuộc gọi đến
            </h5>

            <p>
              Người gọi:
              <strong>
                {{ callerName }}
              </strong>
            </p>

            <p>
              Loại:
              <strong>
                {{
                  isVideoCall
                    ? "Video"
                    : "Thoại"
                }}
              </strong>
            </p>

            <button
              class="btn btn-success me-2"
              @click="acceptCall"
              :disabled="loading"
            >
              ☎️ Trả lời
            </button>

            <button
              class="btn btn-danger"
              @click="rejectCall"
              :disabled="loading"
            >
              ❌ Từ chối
            </button>

          </div>

        </div>

        <!-- ========================= -->
        <!-- ACTIVE CALL -->
        <!-- ========================= -->

        <div
          v-if="isCalling"
          class="card mt-3 border-success"
        >

          <div
            class="card-body"
          >

            <h5>
              🟢 Đang gọi
            </h5>

            <p>
              Đang nói chuyện với:
              <strong>
                {{ callerName }}
              </strong>
            </p>

            <p>
              Loại cuộc gọi:
              <strong>
                {{
                  isVideoCall
                    ? "Video"
                    : "Thoại"
                }}
              </strong>
            </p>

            <button
              class="btn btn-danger"
              @click="hangupCall"
            >
              📵 Kết thúc
            </button>

          </div>

        </div>

        <!-- ========================= -->
        <!-- VIDEO -->
        <!-- ========================= -->

        <div
          v-if="
            isCalling &&
            isVideoCall
          "
          class="mt-4"
        >

          <h5>
            Video Call
          </h5>

          <div class="row">

            <div class="col-md-6">

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
                  min-height: 200px;
                  background: black;
                  border-radius: 8px;
                "
              ></video>

            </div>

            <div class="col-md-6">

              <p>
                Camera người nhận
              </p>

              <video
                ref="remoteVideo"
                autoplay
                playsinline
                style="
                  width: 100%;
                  min-height: 200px;
                  background: black;
                  border-radius: 8px;
                "
              ></video>

            </div>

          </div>

        </div>

        <!-- ========================= -->
        <!-- DEBUG -->
        <!-- ========================= -->

        <div
          class="alert alert-secondary mt-4"
        >

          <strong>
            Debug:
          </strong>

          <div>
            User:
            {{ username || "(chưa đăng nhập)" }}
          </div>

          <div>
            Target:
            {{ friendUsername || "(chưa nhập)" }}
          </div>

          <div>
            Logged:
            {{ loggedIn }}
          </div>

          <div>
            Calling:
            {{ isCalling }}
          </div>

          <div>
            Incoming:
            {{ hasIncomingCall }}
          </div>

          <div>
            Video:
            {{ isVideoCall }}
          </div>

        </div>

      </div>

    </div>

  </div>
</template>
