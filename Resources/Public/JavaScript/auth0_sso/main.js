 $(document).ready(function () {
     var session = document.getElementById('taby').value;
     console.log("Session in start : in ready(): " + session);
     
     // Set default tab if no session or Account
     if (session == "" || session == "Account" || session == null) {
         session = "OIDC_Settings";
     }
     
     // Open the appropriate tab based on session
     if (session == "OIDC_Settings") {
         openTab(null, 'OIDC_Settings');
         $("#oidc_tab_btn").addClass("active");
     } else if (session == "Attribute_Mapping") {
         openTab(null, 'Attribute_Mapping');
         $("#am_tab_btn").addClass("active");
     } else if (session == "Group_Mapping") {
         openTab(null, 'Group_Mapping');
         $("#gm_tab_btn").addClass("active");
     } else if (session == "Upgrade") {
         openTab(null, 'Upgrade');
         $("#upgrade_tab_btn").addClass("active");
     }

    // Add event listeners for tab buttons
    $("#oidc_tab_btn").click(function(event) {
        removeFlashMessage();
        openTab(event, 'OIDC_Settings');
    });
    
    $("#am_tab_btn").click(function(event) {
        removeFlashMessage();
        openTab(event, 'Attribute_Mapping');
    });
    
    $("#gm_tab_btn").click(function(event) {
        removeFlashMessage();
        openTab(event, 'Group_Mapping');
    });
    
    $("#upgrade_tab_btn").click(function(event) {
        removeFlashMessage();
        openTab(event, 'Upgrade');
    });

    $("input[name='grant_type']").click(function () {
        $("input[name='grant_type']").not(this).prop('checked', false);
    });

    // Set the app_type select value based on saved configuration
    var savedAppType = document.getElementById('saved_app_type');
    if (savedAppType && savedAppType.value && savedAppType.value !== '') {
        var appTypeSelect = document.getElementById('app_type');
        if (appTypeSelect) {
            console.log('Setting app_type to:', savedAppType.value);
            appTypeSelect.value = savedAppType.value;
        }
    } else {
        console.log('No saved app_type found or empty value');
    }
    
    // Always call ifOAuthEnabled to set initial visibility of user info div
    // Use setTimeout to ensure DOM is fully loaded and app_type is set
    setTimeout(function() {
        console.log('Initial call to ifOAuthEnabled after DOM load');
        if (typeof window.ifOAuthEnabled === 'function') {
            window.ifOAuthEnabled();
        } else {
            // fallback to local reference if not yet attached
            ifOAuthEnabled();
        }
    }, 200);
    
    // Also call it immediately in case setTimeout is not needed
    if (typeof window.ifOAuthEnabled === 'function') {
        window.ifOAuthEnabled();
    } else {
        ifOAuthEnabled();
    }
    
    // Bind change listener in case inline onchange cannot resolve global symbol
    var appTypeEl = document.getElementById('app_type');
    if (appTypeEl) {
        appTypeEl.addEventListener('change', function() {
            if (typeof window.ifOAuthEnabled === 'function') {
                window.ifOAuthEnabled();
            } else {
                ifOAuthEnabled();
            }
        });
    }
});

function openTab(evt, activeTab) {
    console.log("inside openTab "+activeTab);
    document.getElementById("leftContainer").classList.add("showElement");
    document.getElementById("leftContainer").classList.remove("hideElement");

    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    
    if (activeTab == "Upgrade") {
        document.getElementById(activeTab).style.display = "block";
    }
    else {
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("Support").style.display = "block";
    }

    // Add active class to the clicked button if event exists
    if (evt && evt.currentTarget) {
        evt.currentTarget.className += " active";
    }
}

function removeFlashMessage() {
    document.querySelectorAll('.typo3-messages').forEach(function (a) {
        a.remove();
        console.log("remove typo3 messages.");
    });
}

function ifOAuthEnabled() {
    var appTypeSelect = document.getElementById('app_type');
    var userInfoDiv = document.getElementById('user_info_div');
    
    console.log('ifOAuthEnabled called - appTypeSelect:', appTypeSelect, 'userInfoDiv:', userInfoDiv);
    
    if (appTypeSelect && userInfoDiv) {
        console.log('Current app_type value:', appTypeSelect.value);
        if (appTypeSelect.value == 'OAuth') {
            console.log('Showing User Info Endpoint field for OAuth');
            userInfoDiv.setAttribute('style', 'display: block !important');
        } else {
            console.log('Hiding User Info Endpoint field for OpenID Connect');
            userInfoDiv.setAttribute('style', 'display: none !important');
        }
    } else {
        console.log('Elements not found - appTypeSelect:', !!appTypeSelect, 'userInfoDiv:', !!userInfoDiv);
    }
}

// Expose functions used by inline HTML handlers to the global scope
window.ifOAuthEnabled = ifOAuthEnabled;

// handleCredentialsLocation function removed - checkboxes now work independently

function testConfiguration(url) {
    var mo_oauth_app_name = jQuery("#app_name").val();
    url = url.replace(" ", "");
    var myWindow = window.open(url + '/?RelayState=testconfig&app=' + mo_oauth_app_name, "Test Attribute Configuration", "width=600, height=600");
    myWindow.focus();

}

// Save SP Settings
document.getElementById('submit_oidc_form')?.addEventListener('click', function () {
    const submit_oidc_form = document.getElementById('submit_oidc_form');
    if (submit_oidc_form) {
        submit_oidc_form.value = 'oidc_settings';
        // Log the current app_type value before submission
        var appTypeSelect = document.getElementById('app_type');
        if (appTypeSelect) {
            console.log('Submitting form with app_type:', appTypeSelect.value);
        }
        document.getElementById('oidc_form')?.submit();
        console.log("submit_oidc_form clicked");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const testConfigButton = document.getElementById("test_config");

    if (testConfigButton) {
        testConfigButton.addEventListener("click", function () {
            const feoidcUrl = document.getElementById("feoidc").value;
            if (feoidcUrl) {
                window.open(
                    feoidcUrl + '?RelayState=testconfig',
                    'TestConfigWindow', // window name
                    'width=500,height=500,scrollbars=yes,resizable=yes'
                );
            } else {
                alert("Please enter the OAuth plugin page URL before testing.");
            }
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const grpmapButton = document.getElementById("grpmap");

    if (grpmapButton) {
        grpmapButton.addEventListener("click", function () {
            document.getElementById('grpmap_form')?.submit();
        });
    }
    
    // Credentials location checkboxes now work independently without initialization interference
});

// initializeCredentialsLocation function removed - checkboxes now work independently

// Also expose other inline-used functions
window.testConfiguration = testConfiguration;

document.addEventListener("DOMContentLoaded", function () {
    const appTypeSelect = document.getElementById("app_type");
    const userInfoDiv = document.getElementById("user_info_div");
    const savedAppType = document.getElementById("saved_app_type")?.value;

    function toggleUserInfoField(value) {
        if (value === "OAuth") {
            userInfoDiv.style.display = "block";
        } else {
            userInfoDiv.style.display = "none";
        }
    }

    // Set initial state (when page loads / after saving)
    toggleUserInfoField(savedAppType || appTypeSelect.value);

    // Update on dropdown change
    appTypeSelect.addEventListener("change", function () {
        toggleUserInfoField(this.value);
    });
});
