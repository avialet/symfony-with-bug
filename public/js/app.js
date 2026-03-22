// BUG: No 'use strict'
// BUG: Variables globales partout - pas de module pattern

// BUG: Credentials en dur dans le JS côté client
API_URL = "http://myapp.com/api"  // BUG: HTTP pas HTTPS, variable globale sans var/let/const
API_KEY = "sk-prod-abc123def456ghi789"
ADMIN_PASSWORD = "admin123!"
DB_CONNECTION = "mysql://root:root_password@127.0.0.1:3306/myapp"

// BUG: Cookie sans Secure, HttpOnly, SameSite
document.cookie = "session_token=abc123xyz; path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT"
document.cookie = "user_role=admin; path=/"
document.cookie = "api_key=" + API_KEY + "; path=/"

// ============================================================
// BUG: DOM XSS - innerHTML avec données non sanitisées
// ============================================================
function displayUserProfile(userId) {
    // BUG: Synchronous XMLHttpRequest (deprecated, blocks UI)
    var xhr = new XMLHttpRequest()
    xhr.open('GET', API_URL + '/user/' + userId, false)  // BUG: synchronous = false
    xhr.send()

    var data = JSON.parse(xhr.responseText)  // BUG: No try/catch on JSON.parse

    // BUG: DOM XSS via innerHTML
    document.getElementById('profile').innerHTML = '<h1>' + data.name + '</h1><p>' + data.bio + '</p>'

    // BUG: DOM XSS via document.write
    document.write('<div class="welcome">Welcome ' + data.name + '</div>')
}

// ============================================================
// BUG: eval() avec input utilisateur
// ============================================================
function calculatePrice() {
    var formula = document.getElementById('price-formula').value
    // BUG: eval() avec données utilisateur - Remote Code Execution
    var result = eval(formula)
    document.getElementById('price-result').innerHTML = result
    return result
}

// BUG: setTimeout/setInterval avec des strings (implicite eval)
function startPolling() {
    // BUG: String argument = eval implicite
    setTimeout("checkForUpdates()", 5000)
    setInterval("refreshDashboard()", 10000)
}

// ============================================================
// BUG: Prototype pollution
// ============================================================
function mergeConfig(target, source) {
    for (var key in source) {
        // BUG: Prototype pollution - pas de vérification hasOwnProperty
        // BUG: Pas de blocage sur __proto__, constructor, prototype
        if (typeof source[key] === 'object' && source[key] !== null) {
            if (!target[key]) target[key] = {}
            mergeConfig(target[key], source[key])
        } else {
            target[key] = source[key]
        }
    }
    return target
}

// BUG: Application directe - un attaquant peut modifier Object.prototype
function loadUserSettings() {
    var params = new URLSearchParams(window.location.search)
    var settings = JSON.parse(params.get('settings') || '{}')  // BUG: no try/catch
    mergeConfig({}, settings)  // BUG: Prototype pollution via URL parameter
}

// ============================================================
// BUG: Callback hell (pyramide of doom)
// ============================================================
function loadDashboard() {
    // BUG: Pyramid of doom, no error handling
    $.ajax({
        url: API_URL + '/users',
        headers: { 'X-API-KEY': API_KEY },  // BUG: Envoyer la clé API dans chaque requête
        success: function(users) {
            $.ajax({
                url: API_URL + '/products',
                success: function(products) {
                    $.ajax({
                        url: API_URL + '/orders',
                        success: function(orders) {
                            $.ajax({
                                url: API_URL + '/analytics',
                                success: function(analytics) {
                                    $.ajax({
                                        url: API_URL + '/logs',
                                        success: function(logs) {
                                            // BUG: console.log de données sensibles
                                            console.log('Users:', JSON.stringify(users))
                                            console.log('API Key used:', API_KEY)
                                            console.log('All orders:', JSON.stringify(orders))
                                            renderDashboard(users, products, orders, analytics, logs)
                                        }
                                    })
                                }
                            })
                        }
                    })
                }
            })
        }
        // BUG: No error callback on any AJAX call
    })
}

// ============================================================
// BUG: Mauvaise gestion du this
// ============================================================
function UserManager() {
    this.users = []
    this.apiKey = API_KEY

    // BUG: this perdu dans le callback
    document.getElementById('load-btn').addEventListener('click', function() {
        // BUG: this ne réfère plus à UserManager ici
        this.loadUsers()  // TypeError: this.loadUsers is not a function
    })

    // BUG: this perdu dans setTimeout
    setTimeout(function() {
        this.refreshUsers()  // BUG: this === window ici
    }, 5000)
}

UserManager.prototype.loadUsers = function() {
    console.log('Loading with key:', this.apiKey)  // BUG: Log API key
}

// ============================================================
// BUG: Memory leak - event listeners jamais nettoyés
// ============================================================
function initWidgets() {
    // BUG: Ajoute un listener à chaque appel, jamais nettoyé
    window.addEventListener('resize', function() {
        recalculateLayout()
    })

    window.addEventListener('scroll', function() {
        trackScrollPosition()
    })

    // BUG: Interval jamais clear
    setInterval(function() {
        checkNotifications()
    }, 1000)

    // BUG: Crée des closures qui retiennent de la mémoire
    for (var i = 0; i < 1000; i++) {
        var element = document.createElement('div')
        element.addEventListener('click', function() {
            // BUG: Closure sur var i - toujours i=1000
            console.log('Clicked element ' + i)
        })
        // BUG: Element never appended to DOM but listener keeps reference
    }
}

// ============================================================
// BUG: Regex catastrophique (ReDoS)
// ============================================================
function validateEmail(email) {
    // BUG: ReDoS - backtracking catastrophique
    var regex = /^([a-zA-Z0-9]+\.)*[a-zA-Z0-9]+@([a-zA-Z0-9]+\.)*[a-zA-Z0-9]+$/
    return regex.test(email)
}

function validateUrl(url) {
    // BUG: ReDoS encore pire
    var regex = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w\.\-\?=&]*)*\/?$/
    return regex.test(url)
}

// ============================================================
// BUG: postMessage sans vérification d'origin
// ============================================================
window.addEventListener('message', function(event) {
    // BUG: Pas de vérification event.origin
    // BUG: eval() du contenu du message
    if (event.data.action === 'execute') {
        eval(event.data.code)  // BUG: RCE via postMessage
    }
    if (event.data.action === 'redirect') {
        window.location = event.data.url  // BUG: Open redirect via postMessage
    }
    if (event.data.action === 'update') {
        document.getElementById(event.data.elementId).innerHTML = event.data.html  // BUG: DOM XSS
    }
})

// ============================================================
// BUG: Stockage de données sensibles dans localStorage
// ============================================================
function handleLogin(username, password) {
    // BUG: Storing credentials in localStorage
    localStorage.setItem('username', username)
    localStorage.setItem('password', password)  // BUG: Password in localStorage!
    localStorage.setItem('api_key', API_KEY)
    localStorage.setItem('session', JSON.stringify({
        user: username,
        token: btoa(username + ':' + password),  // BUG: Base64 is not encryption
        role: 'admin',  // BUG: Hardcoded admin role
        loginTime: Date.now()
    }))

    // BUG: alert() pour le feedback utilisateur
    alert('Login successful! Welcome ' + username)
}

// ============================================================
// BUG: Fonctions redéclarées
// ============================================================
function formatDate(date) {
    return date.toString()
}

// BUG: Fonction redéclarée - la première est écrasée silencieusement
function formatDate(date) {
    return date.getTime()
}

// ============================================================
// BUG: == au lieu de ===
// ============================================================
function checkPermission(userRole) {
    // BUG: Loose comparison everywhere
    if (userRole == true) return 'admin'         // BUG: 1 == true, "1" == true
    if (userRole == 0) return 'guest'            // BUG: "" == 0, null == 0
    if (userRole == null) return 'unknown'       // BUG: undefined == null
    if (userRole == '1') return 'moderator'      // BUG: 1 == '1'

    // BUG: Using == for array comparison (always false in JS)
    if ([1,2,3] == [1,2,3]) return 'match'
}

// ============================================================
// BUG: Promise sans catch
// ============================================================
function fetchData() {
    // BUG: No .catch() - unhandled rejection
    fetch(API_URL + '/data')
        .then(function(response) { return response.json() })
        .then(function(data) {
            document.getElementById('data').innerHTML = data.html  // BUG: XSS
        })

    // BUG: Another unhandled promise
    fetch(API_URL + '/sensitive-data', {
        credentials: 'include',  // BUG: Sends cookies to any origin
        mode: 'no-cors'
    }).then(r => r.text()).then(t => console.log('Sensitive:', t))  // BUG: Logging sensitive data
}

// ============================================================
// BUG: Hoisting issues with var in loops
// ============================================================
function processItems(items) {
    // BUG: var hoisted - i shared across all closures
    for (var i = 0; i < items.length; i++) {
        setTimeout(function() {
            // BUG: Will always log items.length, not the correct index
            console.log('Processing item ' + i + ': ' + items[i])
        }, 100)
    }
}

// BUG: Initialize everything on load without checking DOM readiness
loadUserSettings()
startPolling()
initWidgets()
loadDashboard()
