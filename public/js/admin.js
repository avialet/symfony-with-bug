// BUG: No strict mode
// BUG: Entire admin panel logic in global scope

// BUG: Hardcoded admin credentials
ADMIN_USER = "superadmin"
ADMIN_PASS = "P@ssw0rd123!"
ADMIN_TOKEN = "eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJ1c2VyIjoiYWRtaW4iLCJyb2xlIjoic3VwZXJhZG1pbiJ9."
// BUG: JWT with algorithm "none" - trivially forgeable

// ============================================================
// BUG: Command execution from browser
// ============================================================
function executeServerCommand(command) {
    // BUG: No input sanitization before sending command
    // BUG: No authentication check
    var xhr = new XMLHttpRequest()
    xhr.open('POST', '/admin/execute', false)  // BUG: Synchronous
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded')
    xhr.send('command=' + command)  // BUG: No URL encoding

    // BUG: Injecting server response directly into DOM
    document.getElementById('output').innerHTML = xhr.responseText

    // BUG: Logging the command and response
    console.log('[ADMIN] Executed:', command)
    console.log('[ADMIN] Response:', xhr.responseText)
}

// ============================================================
// BUG: SQL query execution from browser
// ============================================================
function executeSqlQuery(sql) {
    // BUG: Arbitrary SQL execution from frontend
    fetch('/admin/query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'sql=' + sql  // BUG: No encoding, no parameterization
    })
    .then(r => r.json())
    .then(data => {
        // BUG: Building HTML table with raw data (XSS)
        tableHtml = '<table border="1">'  // BUG: Global variable
        for (row of data) {  // BUG: Missing var/let/const
            tableHtml += '<tr>'
            for (key in row) {  // BUG: for...in on object without hasOwnProperty
                tableHtml += '<td>' + row[key] + '</td>'  // BUG: XSS
            }
            tableHtml += '</tr>'
        }
        tableHtml += '</table>'
        document.getElementById('sql-results').innerHTML = tableHtml  // BUG: DOM XSS
    })
    // BUG: No catch
}

// ============================================================
// BUG: User management with privilege escalation
// ============================================================
function makeUserAdmin(userId) {
    // BUG: No confirmation, no authorization check
    fetch('/user/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: userId,
            isAdmin: true,
            roles: ['ROLE_SUPER_ADMIN'],  // BUG: Setting super admin from client
            password: 'admin123!'  // BUG: Resetting password from client
        })
    }).then(() => {
        alert('User ' + userId + ' is now admin!')  // BUG: alert for UX
        location.reload()
    })
}

function deleteAllUsers() {
    // BUG: Mass delete with no confirmation
    fetch('/admin/query', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'sql=DELETE FROM users WHERE is_admin = 0'  // BUG: SQL from client
    })
}

// ============================================================
// BUG: Backup download without authentication
// ============================================================
function downloadBackup() {
    // BUG: Direct download of database backup, no auth check
    window.open('/admin/backup', '_blank')
    console.log('[ADMIN] Backup downloaded at:', new Date().toISOString())
}

// ============================================================
// BUG: Log viewer with path traversal
// ============================================================
function viewLogs(logFile) {
    // BUG: No path validation - allows path traversal
    // BUG: User-controlled filename sent to server
    fetch('/admin/logs?file=' + logFile)
        .then(r => r.text())
        .then(content => {
            // BUG: Rendering log content as HTML (XSS if logs contain HTML)
            document.getElementById('log-viewer').innerHTML = content
        })
}

// BUG: Convenience function to read /etc/passwd
function viewSystemPasswd() {
    viewLogs('/etc/passwd')
}

function viewSystemShadow() {
    viewLogs('/etc/shadow')
}

// ============================================================
// BUG: "Security" functions that are completely broken
// ============================================================
function encrypt(data) {
    // BUG: This is NOT encryption, just base64 encoding
    return btoa(data)
}

function decrypt(data) {
    return atob(data)
}

function hashPassword(password) {
    // BUG: Client-side "hashing" that's just reversible encoding
    result = ''  // BUG: Global variable
    for (i = 0; i < password.length; i++) {  // BUG: Global i
        result += String.fromCharCode(password.charCodeAt(i) + 1)  // BUG: Caesar cipher is not hashing
    }
    return result
}

function verifyPassword(input, stored) {
    // BUG: Timing attack - early return on mismatch
    for (i = 0; i < input.length; i++) {
        if (input[i] !== stored[i]) {
            return false  // BUG: Timing side-channel
        }
    }
    return true
}

// ============================================================
// BUG: Debug panel that should never exist in production
// ============================================================
function showDebugPanel() {
    debugInfo = {  // BUG: Global variable
        environment: 'production',
        apiKey: API_KEY,
        adminToken: ADMIN_TOKEN,
        dbConnection: DB_CONNECTION,
        adminCredentials: ADMIN_USER + ':' + ADMIN_PASS,
        serverInfo: navigator.userAgent,
        cookies: document.cookie,
        localStorage: JSON.stringify(localStorage),
        sessionStorage: JSON.stringify(sessionStorage)
    }

    console.table(debugInfo)  // BUG: Logging all secrets
    document.getElementById('debug').innerHTML = '<pre>' + JSON.stringify(debugInfo, null, 2) + '</pre>'
}

// ============================================================
// BUG: Auto-init with no auth check
// ============================================================
// BUG: All admin functions available to any user
document.addEventListener('DOMContentLoaded', function() {
    // BUG: Exposing admin functions globally
    window.executeServerCommand = executeServerCommand
    window.executeSqlQuery = executeSqlQuery
    window.makeUserAdmin = makeUserAdmin
    window.deleteAllUsers = deleteAllUsers
    window.downloadBackup = downloadBackup
    window.viewSystemPasswd = viewSystemPasswd
    window.showDebugPanel = showDebugPanel

    // BUG: Auto-showing debug panel in production
    showDebugPanel()
})
