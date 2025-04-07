<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:v="urn:schemas-microsoft-com:vml"
  xmlns:o="urn:schemas-microsoft-com:office:office"
>
  <head>

    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <title>Email - SonoLinq</title>

    <!-- Start stylesheet -->
    <style type="text/css">
      a,
      a[href],
      a:hover,
      a:link,
      a:visited {
        background-color: #000000;
        font-size: 15px;
        line-height: 22px;
        font-family: "Helvetica", Arial, sans-serif;
        font-weight: normal;
        text-decoration: none;
        padding: 12px 15px;
        color: #ffffff !important;
        border-radius: 5px;
        display: inline-block;
        mso-padding-alt: 0;
        margin-top: 15px;
        padding: 10px 35px;
      }
      .link {
        text-decoration: underline !important;
      }
      p,
      p:visited {
        font-size: 15px;
        line-height: 24px;
        font-family: "Helvetica", Arial, sans-serif;
        font-weight: 400;
        text-decoration: none;
        color: #919293;
        text-align: left;
      }
      h1 {
        font-size: 20px;
        line-height: 24px;
        font-family: "Helvetica", Arial, sans-serif;
        font-weight: 600;
        text-decoration: none;
        color: #000000;
        text-align: left;
      }
      .ExternalClass p,
      .ExternalClass span,
      .ExternalClass font,
      .ExternalClass td {
        line-height: 100%;
      }
      .ExternalClass {
        width: 100%;
      }
    </style>
    <!-- End stylesheet -->
  </head>

  <!-- You can change background colour here -->
  <body
    style="
      text-align: center;
      margin: 0;
      padding-top: 10px;
      padding-bottom: 10px;
      padding-left: 0;
      padding-right: 0;
      -webkit-text-size-adjust: 100%;
      background-color: #f2f4f6;
      color: #000000;
    "
    align="center"
  >
    <div style="text-align: center">
      <table
        align="center"
        style="
          text-align: center;
          vertical-align: top;
          width: 600px;
          max-width: 600px;
          background-color: #0b0c10;
        "
        width="600"
      >
        <tbody>
          <tr>
            <td
              style="
                width: 596px;
                vertical-align: top;
                padding-left: 0;
                padding-right: 0;
                padding-top: 15px;
                padding-bottom: 15px;
              "
              width="596"
            >
              <!-- Your logo is here -->
              <img
                style="
                  width: 350px;
                  max-width: 350px;
                  height: 85px;
                  max-height: 85px;
                  text-align: center;
                  color: #ffffff;
                "
                alt="Logo"
                src="https://sonolinq.com/assets/img/sonoLinq-logo.png"
                align="center"
                width="180"
                height="85"
              />
            </td>
          </tr>
        </tbody>
      </table>
      <!-- End container for logo -->

      <!-- Hero image -->
      <img
        style="
          width: 600px;
          max-width: 600px;
          height: 350px;
          max-height: 350px;
          text-align: center;
        "
        alt="Hero image"
        src="https://sonolinq.com/assets/img/77f5caab3f7d42d0ba0e174c88d9eac7.jpg"
        align="center"
        width="600"
        height="350"
      />
      <!-- Hero image -->

      <!-- Start single column section -->
      <table
        align="center"
        style="
          text-align: center;
          vertical-align: top;
          width: 600px;
          max-width: 600px;
          background-color: #ffffff;
        "
        width="600"
      >
        <tbody>
          <tr>
            <td
              style="
                width: 596px;
                vertical-align: top;
                padding-left: 30px;
                padding-right: 30px;
                padding-top: 30px;
                padding-bottom: 40px;
              "
              width="596"
            >
            {{-- @if ($details['type'] == 'welcome')
                @php
                    $data = str_replace('{{username}}', $details['full_name'], $details['body']);
                    $data = str_replace('{{url}}', $details['url'], $data);
                @endphp

                {!! html_entity_decode($data) !!}
            @endif

            @if ($details['type'] == 'verification')
                @php
                    $data = str_replace('{{username}}', $details['full_name'], $details['body']);
                    $data = str_replace('{{url}}', $details['url'], $data);
                @endphp

                {!! html_entity_decode($data) !!}
            @endif


            @if ($details['type'] == 'forgot-password')
                @php
                    $data = str_replace('{{username}}', $details['full_name'], $details['body']);
                    $data = str_replace('{{url}}', $details['url'], $data);
                    $data = str_replace('{{password}}', $details['password'], $data);
                @endphp

                {!! html_entity_decode($data) !!}
            @endif --}}



            @php
                if ($details['type'] == 'welcome' || $details['type'] == 'verification') {
                    $data = str_replace(['{{username}}', '{{url}}'], [$details['full_name'], $details['url']], $details['body']);
                } elseif ($details['type'] == 'forgot-password') {
                    $data = str_replace(['{{username}}', '{{url}}', '{{password}}'], [$details['full_name'], $details['url'], $details['password']], $details['body']);
                } elseif ($details['type'] == 'booking-request' || $details['type'] == 'booking-accept' || $details['type'] == 'booking-deliver' || $details['type'] == 'booking-complete' || $details['type'] == 'booking-cancel' ) {
                  $data = str_replace(['{{username}}'], [$details['full_name']], $details['body']);
                } elseif ($details['type'] == 'level-upgrade') {
                  $data = str_replace(['{{username}}', '{{level}}'], [$details['full_name'], $details['level']], $details['body']);
                } elseif ($details['type'] == 'level-downgrade') {
                  $data = str_replace(['{{username}}', '{{previous_level}}', '{{latest_level}}'], [$details['full_name'], $details['previous_level'], $details['latest_level']], $details['body']);
                } elseif ($details['type'] == 'inactive-client-email') {
                  $data = str_replace(['{{username}}', '{{reason}}'], [$details['full_name'], $details['reason']], $details['body']);
                } elseif ($details['type'] == 'new-user-admin-email') {
                  $data = str_replace(['{{username}}', '{{useremail}}'], [$details['full_name'], $details['user_email']], $details['body']);
                }
            @endphp

            {!! html_entity_decode($data) !!}

            {{-- <h1>Single column, dolor sit amet</h1>
              <p>
                Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam
                mattis ante sed imperdiet euismod. Vivamus fermentum bibendum
                turpis, et tempor dui. Sed vitae lectus egestas, finibus purus
                ac, rutrum mauris.
              </p>

              <a href="#" target="_blank" >
                Learn more
              </a> --}}
            </td>
          </tr>
        </tbody>
      </table>
      <!-- End single column section -->

      <!-- Start footer -->
      <table
        align="center"
        style="
          text-align: center;
          vertical-align: top;
          width: 600px;
          max-width: 600px;
          background-color: #000000;
        "
        width="600"
      >
        <tbody>
          <tr>
            <td
              style="
                width: 596px;
                vertical-align: top;
                padding-left: 30px;
                padding-right: 30px;
                padding-top: 30px;
                padding-bottom: 30px;
              "
              width="596"
            >
              <!-- Your inverted logo is here -->
              <img
                style="
                  width: 180px;
                  max-width: 180px;
                  height: 50px;
                  max-height: 85px;
                  text-align: center;
                  color: #ffffff;
                "
                alt="Logo"
                src="https://sonolinq.com/assets/img/sonoLinq-logo.png"
                align="center"
                width="180"
                height="85"
              />

              <p
                style="
                  font-size: 13px;
                  line-height: 24px;
                  font-family: 'Helvetica', Arial, sans-serif;
                  font-weight: 400;
                  text-decoration: none;
                  color: #ffffff;
                  text-align: center;
                "
              >
                Address line 1, London, L2 4LN
              </p>

              <p
                style="
                  margin-bottom: 0;
                  font-size: 13px;
                  line-height: 24px;
                  font-family: 'Helvetica', Arial, sans-serif;
                  font-weight: 400;
                  text-decoration: none;
                  color: #ffffff;
                  text-align: center;
                "
              >
                <a
                  target="_blank"
                  style="text-decoration: underline; color: #ffffff"
                  href="https://sonolinq.com"
                >
                  sonolinq.com
                </a>
              </p>
            </td>
          </tr>
        </tbody>
      </table>
      <!-- End footer -->
    </div>
  </body>
</html>
