<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css"/>
<script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootbox.js/4.4.0/bootbox.min.js"></script>


<style>

    .otpcode {
        min-width: 200px;
        max-width: 300px;
        margin: 11px;
    }

    .otpcodelabel {
        padding: 20px;
    }

    #otp_timer {
        padding: 18px;
    }

    .otpinfolabel {
        font-size : 15px;
    }

</style>

<script type="application/javascript">

    function OtpTimer(remaining_time, timesup) {

        var x = setInterval(function () {

            remaining_time--;
            seconds = Math.floor(remaining_time % 60);
            minutes = Math.floor(remaining_time / 60);

            if (!isNaN(remaining_time)) {


                if (seconds < 10) {
                    seconds = '0' + seconds;
                }
                if (minutes < 10) {
                    minutes = '0' + minutes;
                }

                if (minutes == "0") {
                    minutes = '';
                } else {
                    minutes += ' : ';
                }

                document.getElementById("otp_timer").innerHTML = " " + minutes + "" + seconds;
            } else {
                remaining_time = -1;
            }

            if (timesup == 1) {
                remaining_time = -1;
            }

            if (remaining_time < 0) {
                clearInterval(x);
                document.getElementById("otp_timer").innerHTML = "00:00";
            }
        }, 1000);
    }

    function tekrarGonder() {
        var reference_code = document.getElementById("reference_code").value.trim();

        {literal}
        var data = {action: "resendOtp", netgsm_reference_code: reference_code};
        {/literal}

        $.ajax({
            url: 'modules/addons/netgsm/ajax.php',
            type: 'POST',
            data: data,
            success: function (data) {

                data = JSON.parse(data);

                if (data["status"] == 1) {
                    swal({
                        title: "Başarılı!",
                        text: data["message"],
                        type: "success",
                        confirmButtonText: "Tamam",

                    }, function () {
                        // window.location = "clientarea.php";
                    });

                    document.getElementById("reference_code").value = data["reference_code"];
                    OtpTimer(data["remaning_seconds"], 0);

                } else {
                    swal({
                        title: "Başarısız!",
                        text: data["message"],
                        type: "warning",
                        confirmButtonText: "Tamam",

                    }, function () {
                    });
                }
            },
            error: function (data) {
                console.log('Error:', data);
            }
        });
    }

    window.onload = function () {
        const myInput = document.getElementById('otp_code');
        myInput.onpaste = function (e) {
            e.preventDefault();
        }
        document.getElementById('dogrula_button').addEventListener('click', function () {
            var otp_code = document.getElementById('otp_code').value;
        });

        OtpTimer({$remaining_time},{$timesup});
    }

</script>


<form method="POST" action="index.php?m=netgsm&action={$action}" name="otp_form" id="otp_form">
    <div class="row">
        <div class="col-md-12">
            {if isset($message)}
                <div class="alert alert-danger alert-dismissible">
                    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                    {$message}
                </div>
            {/if}
        </div>
    </div>

    <div class="row">
        {* eğer telefon alanı hidden yerine text olursa, whmcs arayüzde numaralar arasına - işareti ekliyor. yazılan kod bu düşünerek yazılmalıdır. *}
        <input type="hidden" name="reference_code" id="reference_code" value="{$reference_code}">

        <label class="otpcodelabel"><span
                    class="otpinfolabel label label-info">{$phonenumber} telefon numarasına gelen doğrulama kodunu giriniz.</span></label>
        <br>
        <input type="text" class="form-control otpcode" name="otp_code" id="otp_code" placeholder="Doğrulama Kodunuz" required>
        <label id="otp_timer" style="font-size: large"></label>
        {*<div class="col-md-3 col-sm-6 col-xs-6" style="margin-top: 5px;">*}
        {*<button type="button" class="btn btn-info" name="resendtOtp" onclick="tekrarGonder()" id="resendOtp">Tekrar Gönder*}
        {*</button>*}
        {*</div>*}
    </div>
    <hr>
    <div class="row">
        <div class="col-md-3 col-sm-12">
            <button type="submit" class="btn btn-success" name="dogrula_button" id="dogrula_button">Doğrula</button>
        </div>
    </div>
    <br>
</form>

