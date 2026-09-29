/*
 * Email verification (OTP) for new accounts
 * -----------------------------------------
 * Used by the admin Add Coordinator and Add User forms.
 * For a new account the save endpoint does not save right away: it emails a
 * 6-digit code to the new account's address and answers "otp_sent". This dialog
 * asks for that code and saves again with it. The server checks the code.
 *
 *   AccountOtp.init(function(otp){ save(otp); });   // otp "" = send / resend a code
 *
 *   success: function(resp){
 *       if(AccountOtp.handle(resp, email)){ end_load(); return; }   // OTP step
 *       if(resp == 1){ AccountOtp.close(); ... }
 *   }
 *
 * Server answers: otp_sent, otp_wait|<seconds>, otp_invalid, otp_expired,
 * otp_locked, mail_error|<reason>, unauthorized.
 */
var AccountOtp = (function($){

    var submit = null;      // page callback: submit(otp)
    var timer = null;       // resend cooldown
    var $modal = null;

    function build(){
        if($modal) return $modal;

        $modal = $(
            '<div class="modal fade otp-modal" id="account_otp_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">' +
                '<div class="modal-dialog modal-dialog-centered" role="document">' +
                    '<div class="modal-content">' +
                        '<button type="button" class="otp-close" data-dismiss="modal" aria-label="Close">&times;</button>' +
                        '<div class="otp-icon"><i class="fas fa-envelope-open-text"></i></div>' +
                        '<h5 class="otp-title">Verify email address</h5>' +
                        '<p class="otp-text">We sent a 6-digit code to <b class="otp-email"></b>. ' +
                            'Ask the account owner for the code to finish creating the account.</p>' +
                        '<input type="text" class="form-control otp-code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000">' +
                        '<div class="otp-message" role="status"></div>' +
                        '<button type="button" class="btn otp-verify">Verify &amp; Save</button>' +
                        '<button type="button" class="btn btn-link otp-resend">Resend code</button>' +
                    '</div>' +
                '</div>' +
            '</div>'
        ).appendTo("body");

        $modal.on("input", ".otp-code", function(){
            this.value = this.value.replace(/\D/g, "").slice(0, 6);
        });
        $modal.on("keydown", ".otp-code", function(e){
            if(e.key === "Enter"){
                e.preventDefault();
                verify();
            }
        });
        $modal.on("click", ".otp-verify", verify);
        $modal.on("click", ".otp-resend", function(){
            message("Sending a new code...", "");
            $(this).prop("disabled", true);
            submit("");
        });
        $modal.on("shown.bs.modal", function(){
            $modal.find(".otp-code").trigger("focus");
        });
        $modal.on("hidden.bs.modal", function(){
            clearInterval(timer);
        });

        return $modal;
    }

    function message(text, type){
        build().find(".otp-message").attr("class", "otp-message " + (type || "")).text(text);
    }

    function busy(on){
        build().find(".otp-code").prop("disabled", on);
        build().find(".otp-verify").prop("disabled", on).html(
            on ? '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying...' : "Verify &amp; Save"
        );
    }

    function cooldown(seconds){
        var $btn = build().find(".otp-resend");
        var left = seconds;
        clearInterval(timer);

        if(left <= 0){
            $btn.prop("disabled", false).text("Resend code");
            return;
        }
        $btn.prop("disabled", true).text("Resend code (" + left + "s)");
        timer = setInterval(function(){
            left--;
            if(left <= 0){
                clearInterval(timer);
                $btn.prop("disabled", false).text("Resend code");
            }else{
                $btn.text("Resend code (" + left + "s)");
            }
        }, 1000);
    }

    function verify(){
        var code = build().find(".otp-code").val();
        if(!/^\d{6}$/.test(code)){
            message("Enter the 6-digit code.", "error");
            return;
        }
        message("", "");
        busy(true);
        submit(code);
    }

    function open(email){
        build().find(".otp-email").text(email);
        build().find(".otp-code").val("");
        busy(false);
        if(!$modal.hasClass("show")){
            $modal.modal("show");
        }
    }

    function is_open(){
        return $modal && $modal.hasClass("show");
    }

    // Shows an error in the dialog, or in the page's #msg box when the dialog is closed
    function error(text){
        if(is_open()){
            busy(false);
            message(text, "error");
        }else{
            $("#msg").html(
                "<div class='alert alert-danger'><i class='fas fa-exclamation-circle mr-2'></i>" +
                $("<div>").text(text).html() + "</div>"
            );
        }
    }

    return {
        init: function(fn){
            submit = fn;
        },

        // Handles the OTP answers from the save endpoint; false for anything else
        handle: function(resp, email){
            resp = $.trim(String(resp));
            var status = resp.split("|")[0];
            var detail = resp.slice(status.length + 1);

            switch(status){
                case "otp_sent":
                    open(email);
                    message("Code sent. It may take a minute to arrive; ask them to check Spam too.", "success");
                    cooldown(60);
                    return true;

                case "otp_wait":
                    open(email);
                    message("A code was already sent to this email. Use that code, or resend in " + detail + "s.", "");
                    cooldown(parseInt(detail, 10) || 60);
                    return true;

                case "otp_invalid":
                    busy(false);
                    message("Wrong code. Please check it and try again.", "error");
                    build().find(".otp-code").trigger("select");
                    return true;

                case "otp_expired":
                    busy(false);
                    message("This code has expired or the email was changed. Click \"Resend code\" to get a new one.", "error");
                    cooldown(0);
                    return true;

                case "otp_locked":
                    busy(false);
                    message("Too many wrong tries. Click \"Resend code\" to get a new one.", "error");
                    cooldown(0);
                    return true;

                case "mail_error":
                    error("The verification email could not be sent: " + detail);
                    if(is_open()) cooldown(0);
                    return true;

                case "unauthorized":
                    error("Your session has expired. Please log in again.");
                    return true;
            }
            return false;
        },

        close: function(){
            if(is_open()){
                $modal.modal("hide");
            }
        }
    };

})(jQuery);
