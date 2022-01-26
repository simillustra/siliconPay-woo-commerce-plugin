jQuery(function ($) {
  let siliconpay_submit = false;

  $("#wc-siliconpay-form").hide();

  wcSiliconPayFormHandler();

  jQuery("#siliconpay-payment-button").click(function () {
    return wcSiliconPayFormHandler();
  });

  jQuery("#siliconpay_form form#order_review").submit(function () {
    return wcSiliconPayFormHandler();
  });

  function createModaltwo(params) {
    $("#SPWCModal").remove(); //remove modal here
    const modal = document.createElement("div");
    modal.classList.add("silicon-pay-modal");
    modal.id = "SPWCModal";
    modal.hidden = false;
    modal.innerHTML = `
  <div class="silicon-pay-modal-content">
    <div class="silicon-pay-modal-header">
      <span class="close">&times;</span>
        <h4 id="silicon-pay-title">Payment Status:</h4>
    </div>
    <div class="silicon-pay-modal-body">
      <p id="silicon-pay-main-text">${params.title}</p>
        <p id="silicon-pay-main-text-2">${params.message}.</p>
    </div>
    <div class="silicon-pay-modal-footer">
      <h6 class="silicon-powered">Powered by Silicon Pay</h6>
   </div>
  </div>
`;

    modal.style.display = "block";

    modal.querySelector(".close").addEventListener("click", () => {
      modal.hidden = true;
    });

    // Get the <span> element that closes the modal
    const span = modal.querySelector(".close");

    // When the user clicks on <span> (x), close the modal
    span.onclick = function () {
      modal.style.display = "none";
    };

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    };

    document.body.appendChild(modal);
  }

  function wcSiliconPayCustomFields() {
    let custom_fields = [
      {
        display_name: "Plugin",
        variable_name: "plugin",
        value: "woo-siliconpay",
      },
    ];

    if (wc_siliconpay_app_params.meta_order_id) {
      custom_fields.push({
        display_name: "Order ID",
        variable_name: "order_id",
        value: wc_siliconpay_app_params.meta_order_id,
      });
    }

    if (wc_siliconpay_app_params.meta_name) {
      custom_fields.push({
        display_name: "Customer Name",
        variable_name: "customer_name",
        value: wc_siliconpay_app_params.meta_name,
      });
    }

    if (wc_siliconpay_app_params.meta_email) {
      custom_fields.push({
        display_name: "Customer Email",
        variable_name: "customer_email",
        value: wc_siliconpay_app_params.meta_email,
      });
    }

    if (wc_siliconpay_app_params.meta_phone) {
      custom_fields.push({
        display_name: "Customer Phone",
        variable_name: "customer_phone",
        value: wc_siliconpay_app_params.meta_phone,
      });
    }

    if (wc_siliconpay_app_params.meta_billing_address) {
      custom_fields.push({
        display_name: "Billing Address",
        variable_name: "billing_address",
        value: wc_siliconpay_app_params.meta_billing_address,
      });
    }

    if (wc_siliconpay_app_params.meta_shipping_address) {
      custom_fields.push({
        display_name: "Shipping Address",
        variable_name: "shipping_address",
        value: wc_siliconpay_app_params.meta_shipping_address,
      });
    }

    if (wc_siliconpay_app_params.meta_products) {
      custom_fields.push({
        display_name: "Products",
        variable_name: "products",
        value: wc_siliconpay_app_params.meta_products,
      });
    }

    return custom_fields;
  }

  /**
   * @function validatePhone
   * @param {*} number
   * @returns
   */

  function validatePhone(number) {
    let new_number = "";
    if (number === "") {
      return false;
    } else if (number.startsWith("+")) {
      new_number = number.replace(number[0], "");
    } else if (number.startsWith("0")) {
      new_number = number.replace(number[0], "256");
    } else {
      new_number = number;
    }
    return new_number;
  }
  /**
   * @function wcSiliconPayFormHandler
   */
  function wcSiliconPayFormHandler() {
    $("#wc-siliconpay-form").hide();

    if (siliconpay_submit) {
      siliconpay_submit = false;
      return true;
    }

    let $form = $("form#payment-form, form#order_review"),
      siliconpay_txnref = $form.find("input.siliconpay_txnref");
    siliconpay_txnref.val("");

    let amount = Number(wc_siliconpay_app_params.amount);
    var currency = wc_siliconpay_app_params.currency;
    var phone = wc_siliconpay_app_params.meta_phone;
    var email = wc_siliconpay_app_params.email;
    var payment = $(this).find("#pf-wc-method option:selected").val();

    if (currency == "UGX") {
      phone = validatePhone(phone);
    }

    if (currency == "USD" || payment == "Credit/Debit Card") {
      method = "card_payment";
    } else {
      method = "mobile_money";
    }

    const woo_sp_settings = {
      url: "https://silicon-pay.com/process_payments",
      method: "POST",
      timeout: 0,
      crossDomain: true,
      data: JSON.stringify({
        req: method,
        currency: currency,
        encryption_key: wc_siliconpay_app_params.key,
        amount: amount,
        emailAddress: email,
        phone: phone,
        ref: wc_siliconpay_app_params.txnref,
        metadata: {
          custom_fields: wcSiliconPayCustomFields(),
        },
      }),
    };

    $.ajax(woo_sp_settings)
      .done(function (response) {
        //console.log("woo-response", response, response.code);
        // $.blockUI({ message: "Please wait..." });
        var response_link = response.link ? response.link : null;
        var response_code = response.code ? response.code : "201";
        var response_message = response.message
          ? response.message
          : "Unknown Error!";

        var credit_card_message =
          response_link !== null
            ? response_message +
              " You would be redirected in 10 seconds to a GTBank page to complete your card Transaction."
            : response_message;

        createModaltwo({
          title: "Your payment is being processed.",
          message: credit_card_message,
        });

        setTimeout(() => {
          $form.append(
            '<input type="hidden" class="siliconpay_txnref" name="siliconpay_txnref" value="' +
              wc_siliconpay_app_params.txnref +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_payment_link" name="siliconpay_payment_link" value="' +
              response_link +
              '"/>'
          );

          $form.append(
            '<input type="hidden" class="siliconpay_status_code_report" name="siliconpay_status_code_report" value="' +
              response_code +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_status" name="siliconpay_status" value="' +
              response.status +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_message" name="siliconpay_message" value="' +
              response_message +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_amount" name="siliconpay_amount" value="' +
              amount +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_currency" name="siliconpay_currency" value="' +
              currency +
              '"/>'
          );
          $form.append(
            '<input type="hidden" class="siliconpay_reference" name="siliconpay_reference" value="' +
              response.txRef +
              '"/>'
          );

          siliconpay_submit = true;

          $form.submit();

          $("body").block({
            message: null,
            overlayCSS: {
              background: "#fff",
              opacity: 0.6,
            },
            css: {
              cursor: "wait",
            },
          });
        }, 6000);

        if (response_link !== null) {
          setTimeout(() => {
            window.location.href = data.payment_link;
          }, 10000);
        }
      })
      .fail(function (failedResponse) {
        //console.log("failedResponse", failedResponse);
        createModaltwo({
          title: "Your payment has encountered errors.",
          message: failedResponse.message,
        });
      })
      .always(function (completedResponse) {
        console.log("completedResponse", completedResponse);
        $("#wc-siliconpay-form").show();
        $(this.el).unblock();
      });

    return false;
  }
});
