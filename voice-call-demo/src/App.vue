<script setup>
import { ref } from "vue";
import {
  StringeeClient,
  StringeeCall,
  StringeeCall2,
} from "stringee";

// =========================
// STATE
// =========================

const loggedIn = ref(false);

const username = ref("");
const friendUsername = ref("");

const accessToken = ref("");

const callStatus = ref("");
const friendName = ref("");

const loading = ref(false);
const isCalling = ref(false);
const hasIncomingCall = ref(false);
const isVideoCall = ref(false);

const localVideo = ref(null);
const remoteVideo = ref(null);

// =========================
// STRINGEE CLIENT
// =========================

const stringeeClient = new StringeeClient();

let call = null;

// =========================
// CALL EVENTS
// =========================

function settingCallEvent(call1) {
  if (!call1) {
    console.error("CALL OBJECT IS NULL");
    return;
  }

  // -------------------------
  // Local stream
  // -------------------------

  call1.on("addlocalstream", function (stream) {
    console.log("addlocalstream", stream);

    if (localVideo.value) {
      localVideo.value.srcObject = null;
      localVideo.value.srcObject = stream;
    }
  });

  // -------------------------
  // Remote stream
  // -------------------------

  call1.on("addremotestream", function (stream) {
    console.log("addremotestream", stream);

    if (remoteVideo.value) {
      remoteVideo.value.srcObject = null;
      remoteVideo.value.srcObject = stream;
    }
  });

  // -------------------------
  // Signaling
  // -------------------------

  call1.on("signalingstate", function (state) {
    console.log("SIGNALING STATE:", state);

    callStatus.value =
      state.reason ||
      state.message ||
      "";

    // Call accepted
    if (state.code === 3) {
      console.log("CALL ACCEPTED");

      isCalling.value = true;
      loading.value = false;
      hasIncomingCall.value = false;
    }

    // Call ended
    if (
      state.code === 4 ||
      state.code === 5 ||
      state.code === 6
    ) {
      console.log("CALL ENDED");

      isCalling.value = false;
      loading.value = false;
      hasIncomingCall.value = false;
    }
  });

  // -------------------------
  // Media state
  // -------------------------

  call1.on("mediastate", function (state) {
    console.log("MEDIA STATE:", state);
  });

  // -------------------------
  // Info
  // -------------------------

  call1.on("info", function (info) {
    console.log("CALL INFO:", info);
  });

  // -------------------------
  // Error
  // -------------------------

  call1.on("error", function (error) {
    console.error("CALL ERROR:", error);

    loading.value = false;
    isCalling.value = false;

    let message = "Lỗi cuộc gọi";

    if (error) {
      message =
        error.message ||
        error.reason ||
        JSON.stringify(error);
    }

    callStatus.value = message;

    alert("Lỗi cuộc gọi:\n" + message);
  });
}

// =========================
// CLIENT CONNECT
// =========================

stringeeClient.on("connect", () => {
  console.log("Connected to StringeeServer");
});

// =========================
// AUTHENTICATION
// =========================

stringeeClient.on("authen", (res) => {
  console.log("AUTHEN RESULT:", res);

  if (res.r === 0 || res.message === "SUCCESS") {
    loggedIn.value = true;

    console.log(
      "Đăng nhập Stringee thành công:",
      username.value
    );
  } else {
    loggedIn.value = false;

    alert(
      "Đăng nhập Stringee thất bại:\n" +
        (res.message || JSON.stringify(res))
    );
  }
});

// =========================
// INCOMING CALL
// =========================

stringeeClient.on("incomingcall", (incomingcall) => {
  console.log("INCOMING CALL:", incomingcall);

  call = incomingcall;

  settingCallEvent(incomingcall);

  hasIncomingCall.value = true;
  isCalling.value = false;
  loading.value = false;

  isVideoCall.value =
    incomingcall.isVideoCall || false;

  friendName.value =
    incomingcall.fromNumber || "Người gọi";
});

// =========================
// LOGIN
// =========================

const onLogin = async () => {
  if (!username.value.trim()) {
    alert("Vui lòng nhập User ID");
    return;
  }

  loading.value = true;
  callStatus.value = "Đang đăng nhập...";

  try {
    const endpoint =
      import.meta.env.VITE_JWT_ENDPOINT;

    if (!endpoint) {
      throw new Error(
        "Chưa cấu hình VITE_JWT_ENDPOINT"
      );
    }

    // Không thêm ?u nếu endpoint đã có query
    const separator = endpoint.includes("?")
      ? "&"
      : "?";

    const url =
      `${endpoint}${separator}u=${encodeURIComponent(
        username.value.trim()
      )}`;

    console.log("JWT URL:", url);

    const res = await fetch(url);

    if (!res.ok) {
      throw new Error(
        `HTTP ${res.status}`
      );
    }

    const data = await res.json();

    console.log("TOKEN RESPONSE:", data);

    if (!data.access_token) {
      throw new Error(
        "Server không trả access_token"
      );
    }

    accessToken.value =
      data.access_token;

    console.log(
      "Connecting Stringee..."
    );

    stringeeClient.connect(
      accessToken.value
    );
  } catch (error) {
    console.error(
      "LOGIN ERROR:",
      error
    );

    loading.value = false;

    alert(
      "Không thể đăng nhập:\n" +
        error.message
    );
  }
};

// =========================
// OUTGOING CALL
// =========================

const onCall = async (
  videoCall = false
) => {
  if (loading.value || isCalling.value) {
    return;
  }

  if (!loggedIn.value) {
    alert(
      "Bạn chưa đăng nhập Stringee"
    );
    return;
  }

  if (!friendUsername.value.trim()) {
    alert(
      "Vui lòng nhập ID người nhận"
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

  loading.value = true;
  isVideoCall.value = videoCall;
  callStatus.value =
    "Đang tạo cuộc gọi...";

  console.log(
    "========================"
  );

  console.log(
    "CALL FROM:",
    from
  );

  console.log(
    "CALL TO:",
    to
  );

  console.log(
    "VIDEO:",
    videoCall
  );

  console.log(
    "========================"
  );

  try {
    // =======================
    // VOICE CALL
    // =======================

    if (!videoCall) {
      console.log(
        "Creating StringeeCall..."
      );

      call = new StringeeCall(
        stringeeClient,
        from,
        to
      );
    }

    // =======================
    // VIDEO CALL
    // =======================

    else {
      console.log(
        "Creating StringeeCall2..."
      );

      call = new StringeeCall2(
        stringeeClient,
        from,
        to,
        true
      );
    }

    if (!call) {
      throw new Error(
        "Không tạo được call object"
      );
    }

    settingCallEvent(call);

    // =======================
    // MAKE CALL
    // =======================

    call.makeCall(function (res) {
      console.log(
        "MAKE CALL RESULT:",
        res
      );

      // Thành công
      if (res.r === 0) {
        console.log(
          "CALL REQUEST SUCCESS"
        );

        friendName.value =
          res.toNumber || to;

        callStatus.value =
          "Đang chờ người nhận...";

        // Không set isCalling=true
        // ở đây.
        // Chờ signalingstate code 3.
      }

      // Thất bại
      else {
        console.error(
          "MAKE CALL FAILED:",
          res
        );

        loading.value = false;
        isCalling.value = false;

        const message =
          res.message ||
          "Không thể thực hiện cuộc gọi";

        callStatus.value =
          message;

        alert(
          "Gọi thất bại!\n\n" +
            "Code: " +
            res.r +
            "\n" +
            "Message: " +
            message
        );
      }
    });
  } catch (error) {
    console.error(
      "MAKE CALL EXCEPTION:",
      error
    );

    loading.value = false;
    isCalling.value = false;

    callStatus.value =
      error.message;

    alert(
      "Lỗi tạo cuộc gọi:\n" +
        error.message
    );
  }
};

// =========================
// ACCEPT
// =========================

const acceptCall = () => {
  if (!call) {
    alert(
      "Không tìm thấy cuộc gọi"
    );
    return;
  }

  console.log(
    "ANSWER CALL"
  );

  loading.value = true;

  call.answer(function (res) {
    console.log(
      "ANSWER RESULT:",
      res
    );

    if (res.r === 0) {
      hasIncomingCall.value =
        false;

      isCalling.value = true;
      loading.value = false;

      callStatus.value =
        "Đã kết nối";
    } else {
      loading.value = false;

      alert(
        "Không thể trả lời:\n" +
          (res.message ||
            JSON.stringify(res))
      );
    }
  });
};

// =========================
// REJECT
// =========================

const rejectCall = () => {
  if (!call) {
    return;
  }

  console.log(
    "REJECT CALL"
  );

  call.reject(function (res) {
    console.log(
      "REJECT RESULT:",
      res
    );

    hasIncomingCall.value =
      false;

    loading.value = false;
    isCalling.value = false;

    callStatus.value =
      "Đã từ chối cuộc gọi";
  });
};

// =========================
// HANG UP
// =========================

const hangupCall = () => {
  if (!call) {
    return;
  }

  console.log(
    "HANGUP CALL"
  );

  call.hangup(function (res) {
    console.log(
      "HANGUP RESULT:",
      res
    );

    isCalling.value = false;
    loading.value = false;
    hasIncomingCall.value = false;

    callStatus.value =
      "Đã kết thúc cuộc gọi";

    friendName.value = "";
  });
};

// =========================
// UPGRADE VOICE -> VIDEO
// =========================

const upgradeToVideoCall = () => {
  if (!call) {
    return;
  }

  try {
    call.upgradeToVideoCall();

    isVideoCall.value = true;
  } catch (error) {
    console.error(
      "UPGRADE VIDEO ERROR:",
      error
    );
  }
};
</script>

<template>
  <div class="container py-4">
    <div class="row">
      <div class="col-md-8 mx-auto">

        <h1 class="mb-4">
          Demo: Voice Call & Video Call
        </h1>

        <!-- ===================== -->
        <!-- STATUS -->
        <!-- ===================== -->

        <div class="alert alert-info">
          <strong>Trạng thái:</strong>

          {{
            loggedIn
              ? `Đã đăng nhập (${username})`
              : "Chưa đăng nhập"
          }}
        </div>

        <!-- ===================== -->
        <!-- LOGIN -->
        <!-- ===================== -->

        <form
          v-if="!loggedIn"
          @submit.prevent="onLogin"
        >
          <div class="mb-3">

            <label
              for="username"
              class="form-label"
            >
              Stringee User ID
            </label>

            <input
              id="username"
              type="text"
              v-model="username"
              class="form-control"
              placeholder="Ví dụ: user_a"
              autofocus
              required
              :disabled="loading"
            />

          </div>

          <button
            type="submit"
            class="btn btn-primary"
            :disabled="loading"
          >
            {{
              loading
                ? "Đang đăng nhập..."
                : "Đăng nhập"
            }}
          </button>
        </form>

        <!-- ===================== -->
        <!-- CALL FORM -->
        <!-- ===================== -->

        <form
          v-else
          @submit.prevent="onCall(false)"
        >

          <div class="mb-3">

            <label
              for="friend-username"
              class="form-label"
            >
              User ID người nhận
            </label>

            <input
              id="friend-username"
              type="text"
              v-model="friendUsername"
              class="form-control"
              placeholder="Ví dụ: user_b"
              :disabled="
                isCalling || loading
              "
              required
            />

            <div class="form-text">
              Nhập Stringee User ID của người
              muốn gọi.
            </div>

          </div>

          <!-- VOICE -->

          <button
            type="submit"
            class="btn btn-primary"
            :disabled="
              loading || isCalling
            "
          >
            📞

            {{
              loading
                ? "Đang gọi..."
                : "Gọi thoại"
            }}
          </button>

          <!-- VIDEO -->

          <button
            type="button"
            class="btn btn-secondary ms-3"
            :disabled="
              loading || isCalling
            "
            @click="onCall(true)"
          >
            🎥

            {{
              loading
                ? "Đang gọi..."
                : "Gọi video"
            }}
          </button>

        </form>

        <!-- ===================== -->
        <!-- CALL STATUS -->
        <!-- ===================== -->

        <div
          v-if="callStatus"
          class="alert alert-secondary mt-3"
        >
          <strong>
            Trạng thái cuộc gọi:
          </strong>

          {{ callStatus }}
        </div>

        <!-- ===================== -->
        <!-- INCOMING CALL -->
        <!-- ===================== -->

        <div
          v-if="hasIncomingCall"
          class="alert alert-warning mt-3"
        >

          <h5>
            📞 Cuộc gọi đến
          </h5>

          <p>
            Bạn nhận được cuộc gọi từ:
            <strong>
              {{ friendName }}
            </strong>
          </p>

          <button
            class="btn btn-success me-3"
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

        <!-- ===================== -->
        <!-- ACTIVE CALL -->
        <!-- ===================== -->

        <div
          v-if="isCalling"
          class="alert alert-success mt-3"
        >

          <h5>
            🟢 Đang trong cuộc gọi
          </h5>

          <p>
            Đang gọi với:
            <strong>
              {{ friendName }}
            </strong>
          </p>

          <p v-if="callStatus">
            {{ callStatus }}
          </p>

          <button
            class="btn btn-danger"
            @click="hangupCall"
          >
            📵 Kết thúc
          </button>

        </div>

        <!-- ===================== -->
        <!-- VIDEO -->
        <!-- ===================== -->

        <div
          v-if="isCalling && isVideoCall"
          class="mt-4"
        >

          <h5>
            Video
          </h5>

          <div
            class="d-flex flex-wrap gap-3"
          >

            <div>
              <p>
                Camera của bạn
              </p>

              <video
                ref="localVideo"
                autoplay
                muted
                playsinline
                style="
                  width: 300px;
                  max-width: 100%;
                  background: #000;
                "
              ></video>
            </div>

            <div>
              <p>
                Camera người nhận
              </p>

              <video
                ref="remoteVideo"
                autoplay
                playsinline
                style="
                  width: 300px;
                  max-width: 100%;
                  background: #000;
                "
              ></video>
            </div>

          </div>

        </div>

      </div>
    </div>
  </div>
</template>
