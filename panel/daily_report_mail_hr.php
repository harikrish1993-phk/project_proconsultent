<!DOCTYPE html>
<html>

<head>
    <title>Mobility - <?php echo COMPANY_NAME; ?></title>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            line-height: 1.5em;
        }

        /* Container */
        .container {
            width: 100%;
            margin: 0 auto;
        }

        /* Main Table */
        .main-table {
            width: 100%;                  
            background-color: #ffffff;
            border: 1px solid #cccccc;
            border-spacing: 0;
            text-align: left;
        }

        /* Content */
        .content {
            padding: 36px 30px 42px 30px;
            color: #153643;
            font-size: 14px;
        }

        .img-class {
            display: block;
            margin: 0 auto;
            width: 65%;
        }
    </style>
</head>

<body>
    
    <div class="container">
            <table class="main-table" role="presentation">
                <tbody>
                    <!-- First Row: Image -->
                    <tr>
                        <td colspan="2" align="center">                            
                            <img class="img-class" src="https://proconsultancy.be/panel/assets/pro_new.png" style="width: 30%;padding: 1%;" />
                        </td>
                    </tr>
                    <!-- Second Row: Content -->
                    <tr>
                        <td colspan="2" class="content" style="padding-bottom:0px">
                            <p>I hope this message finds you well.</p>
                            <p>
                                As of today, a total of {{total}} candidates have been registered by HR in our portal. For a comprehensive overview of the details, please refer to the table below. If you have any questions or require further information, feel free to reach out.
                                
                               </p>
                            <p>Thank you for your attention.</p>
                            {{table}}</br></br></br>
                            <p><strong>Best regards,</strong></p>
                            <p><strong>Admin Department</strong></p>
                        </td>
                    </tr>
                    <!-- New Row: Additional Content -->
                    <tr>
                        <td colspan="2">
                            <tr id="footerRow">
                                <td colspan="2" style="text-align: center; border: 1px solid grey; background-color: skyblue;">
                                    <p id="footerText" style="font-size: 12px;">&copy; 2024 Pro consultancy</p>
                                </td>
                            </tr>                            
                        </td>
                    </tr>
                    
                </tbody>
            </table>
    </div>
</body>

</html>
