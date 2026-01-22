<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Security Scan Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1 { color: #333; }
        .section { margin-bottom: 20px; }
        .title { font-weight: bold; font-size: 18px; }
        .content { margin-left: 10px; }
    </style>
</head>
<body>

    <h1>Website Security Scan Report</h1>

    <div class="section">
        <div class="title">URL:</div>
        <div class="content">{{ $scan->url }}</div>
    </div>

    <div class="section">
        <div class="title">XSS Test Result:</div>
        <div class="content">{{ $scan->results->xss_result }}</div>
    </div>

    <div class="section">
        <div class="title">SQL Injection Result:</div>
        <div class="content">{{ $scan->results->sql_result }}</div>
    </div>

    <div class="section">
        <div class="title">Headers Result:</div>
        <div class="content">{{ $scan->results->headers_result }}</div>
    </div>

    <div class="section">
        <div class="title">AI Analysis:</div>
        <div class="content">{!! nl2br(e($scan->results->ai_analysis)) !!}</div>
    </div>

</body>
</html>
