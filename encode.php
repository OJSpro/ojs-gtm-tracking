<?php
$dlStr = "<script>\nwindow.dataLayer = window.dataLayer || [];\nwindow.dataLayer.push({ldelim}\n'event': 'payment_success',\n'transaction_id': '{\$paymentId|escape}',\n'order_id': '{\$orderId|escape}',\n'submission_id': '{\$submissionId|escape}'\n{rdelim});\n</script>";
$scriptTpl = "{literal}\n<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','%s');</script>\n<!-- End Google Tag Manager -->\n{/literal}";
$noscriptTpl = "{literal}\n<!-- Google Tag Manager (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=%s\"\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Google Tag Manager (noscript) -->\n{/literal}";

echo "DL: " . base64_encode($dlStr) . "\n";
echo "Script: " . base64_encode($scriptTpl) . "\n";
echo "Noscript: " . base64_encode($noscriptTpl) . "\n";
