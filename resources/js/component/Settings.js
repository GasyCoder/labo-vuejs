let theme = [];
let date = new Date();

function setSettingsCookie(data, exdays) {
    let day = (exdays) ? exdays : 7;
    date.setTime(date.getTime() + (day*24*60*60*1000));
    let expires = "expires="+ date.toUTCString(), cookieData = null;
 
    cookieData = (Array.isArray(data)) ? data.join("=") : data;
    if (cookieData !== null) {
        document.cookie =  cookieData + ";" + expires + ";path=/";
    }
}

function getSettingCookie(cname, dval) {
    let name = cname + "=";
    let decCookie = decodeURIComponent(document.cookie);
    let cookies = decCookie.split(';');

    for(let i = 0; i <cookies.length; i++) {
        let ca = cookies[i];
        while (ca.charAt(0) == ' ') {
            ca = ca.substring(1);
        }

        if (ca.indexOf(name) == 0) {
            return ca.substring(name.length, ca.length);
        }
    }
    return (dval) ? dval : "";
}

function getSettings(theme) {
    let defData = { skin: 'light', direction: 'ltr', sidebar: 'dark', header: 'light' };
    let themeData = {...defData, ...theme};

    let cookieData = { 
        skin: getSettingCookie('skin', themeData.skin), 
        direction: getSettingCookie('direction', themeData.direction), 
        sidebar: getSettingCookie('sidebar', themeData.sidebar),
        header: getSettingCookie('header', themeData.header)
    };
    return cookieData;
}

function onPageLoad(theme){
    let cookieData = getSettings(theme);
    const localSkin = window.localStorage.getItem('skin');
    const skin = localSkin || cookieData.skin;

    if (skin === 'dark'){
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    if (cookieData.direction === 'rtl'){
        document.body.setAttribute('dir', 'rtl');
    } else {
        document.body.setAttribute('dir', 'ltr');
    }
}

export default function Settings(defTheme){    
    onPageLoad(defTheme);

    const themeToggle = document.querySelectorAll('.theme-toggle');
    const directionToggle = document.querySelectorAll('.direction-toggle');

    // mode toggle
    themeToggle.forEach((item)=> {
        if (item.dataset.settingsThemeBound === '1') {
            return;
        }

        item.dataset.settingsThemeBound = '1';
        item.addEventListener('click', function(e){
            e.preventDefault();
            if (!document.documentElement.classList.contains('dark')) {
                setSettingsCookie(['skin', 'dark']);
                window.localStorage.setItem('skin', 'dark');
                document.documentElement.classList.add('dark');
            } else {
                setSettingsCookie(['skin', 'light']);
                window.localStorage.setItem('skin', 'light');
                document.documentElement.classList.remove('dark');
            }
        })
    })

    // direction toggle
    directionToggle.forEach((item)=> {
        if (item.dataset.settingsDirectionBound === '1') {
            return;
        }

        item.dataset.settingsDirectionBound = '1';
        item.addEventListener('click', function(e){
            e.preventDefault();
            let dir = document.body.getAttribute('dir');

            if (dir=='ltr') {
                setSettingsCookie(['direction', 'rtl']);
                document.body.setAttribute('dir', 'rtl');
            } else {
                setSettingsCookie(['direction', 'ltr']);
                document.body.setAttribute('dir', 'ltr');
            }
        })
    })

}
