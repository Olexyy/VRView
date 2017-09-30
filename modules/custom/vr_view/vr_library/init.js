
var vrView;
var startView;
var startImage;
var views;
var linkAddExisting;
var linkAddNew;
var linkDefaultYaw;
var currentVrViewId;
var defaultYaw;
var defaultYawReal;
var mode;
var modes = new Object({
    typeAdmin : 'admin',
    typeSelector : 'selector',
    typeUser : 'user'
});
var linkHotspotPosition;

function setSourceParams() {
    mode = drupalSettings.vr_view.mode;
    startImage = drupalSettings.vr_view.start_image;
    startView = drupalSettings.vr_view.start_view;
    views = drupalSettings.vr_view.views;
    linkAddExisting = drupalSettings.vr_view.link_add_existing;
    linkAddNew = drupalSettings.vr_view.link_add_new;
    linkDefaultYaw = drupalSettings.vr_view.link_default_yaw;
    currentVrViewId = views[startView]['id'];
    linkHotspotPosition = drupalSettings.vr_view.link_hotspot_position
}

function onLoad() {
    setSourceParams();
    vrView = new VRView.Player('#vrview', {
    width: '100%',
    height: 480,
    image: startImage,
    preview: startImage,
    is_stereo: false,
    is_yaw_only: true,
    is_autopan_off: true
    });
    vrView.on('ready', onVRViewReady);
    vrView.on('modechange', onModeChange);
    vrView.on('error', onVRViewError);
    vrView.on('click', onVRViewClick);
    vrView.on('getposition', onVRViewPosition);
    vrView.on('focus', onVRViewFocus);
    vrView.on('blur', onVRViewBlur);
}

function onVRViewReady(e) {
    console.log('onVRViewReady');
    loadScene(startView);
}

function onVRViewBlur(e) {
    console.log('onVRViewBlur', e);
    var wrapper = document.getElementById('vrview');
    var child = document.getElementsByClassName('hotspot-details')[0];
    wrapper.removeChild(child);
}

function onVRViewFocus(e) {
    console.log('onVRViewFocus', e);
    var div = document.createElement('DIV');
    div.innerHTML = views[e.data.id]['name'];
    div.classList.add('hotspot-details');
    div.style.top = (e.data.y)+'px';
    div.style.left = (e.data.x)+'px';
    var wrapper = document.getElementById('vrview');
    wrapper.appendChild(div);
}

function onVRViewClick(e) {
    console.log('onVRViewClick', e.id);
    if (e.id) {
        loadScene(e.id);
    }
    else {
        vrView.getPosition();
    }
}

function loadScene(id) {
    console.log('loadScene', id);
    currentVrViewId = views[id]['id'];
    defaultYawReal = views[id]['default_yaw'];
    if(mode === modes.typeUser)
        defaultYaw = defaultYawReal;
    else
        defaultYaw = 0;
    var newEnding = '/'+currentVrViewId+'/'+defaultYaw+'/0';
    if(document.getElementById('dynamic-button-add-existing'))
        document.getElementById('dynamic-button-add-existing').setAttribute('href', linkAddExisting + newEnding);
    if(document.getElementById('dynamic-button-add-new'))
        document.getElementById('dynamic-button-add-new').setAttribute('href', linkAddNew + newEnding);
    if(document.getElementById('dynamic-button-default-yaw'))
        document.getElementById('dynamic-button-default-yaw').setAttribute('href', linkDefaultYaw + '/'+currentVrViewId + '/' + defaultYaw);
    if(document.getElementById('default-yaw-value'))
        document.getElementById('default-yaw-value').innerHTML = defaultYawReal.toString();
    if(document.getElementById('vrview-title'))
        document.getElementById('vrview-title').innerHTML = views[id]['name'];
    if(document.getElementById('vrview-description'))
        document.getElementById('vrview-description').innerHTML = views[id]['description'];
    if(document.getElementById('hotspots-link-placeholder'))
        document.getElementById('hotspots-link-placeholder').innerHTML = '';
    if(document.getElementById('hotspots-link-placeholder')) {
        var hotspots = views[id]['hotspots'];
        for (var hotspot in hotspots) {
            if (hotspots.hasOwnProperty(hotspot)) {
                var new_link = document.createElement('A');
                new_link.innerHTML = 'Set current pitch and yaw to: ' + hotspots[hotspot]['name'];
                new_link.setAttribute('class', 'dynamic-button-hotspot-position button-action button dynamic-args');
                new_link.setAttribute('hotspot', hotspots[hotspot]['id']);
                new_link.setAttribute('href', linkHotspotPosition + '/' + hotspots[hotspot]['id'] + newEnding);
                document.getElementById('hotspots-link-placeholder').appendChild(new_link);
            }
        }
    }
    // TODO separate func for elem and set default pitch  and yaw...
    vrView.setContent({
        image: views[id]['source'],
        preview: views[id]['source'],
        is_stereo: views[id]['is_stereo'],
        default_yaw: defaultYaw,
        is_yaw_only: true,
        is_autopan_off: true
    });
    // Add all the hotspots for the scene
    var sceneHotSpots = views[id]['hotspots'];
    for (var hotSpotKey in sceneHotSpots) {
        if(sceneHotSpots.hasOwnProperty(hotSpotKey)) {
            vrView.addHotspot(hotSpotKey, {
                pitch: sceneHotSpots[hotSpotKey]['pitch'],
                yaw: sceneHotSpots[hotSpotKey]['yaw'],
                radius: sceneHotSpots[hotSpotKey]['radius'],
                distance: sceneHotSpots[hotSpotKey]['distance']
            });
        }
    }
    console.log('loadedScene', id);
}

function onVRViewPosition(e) {
	var pitch = e.Pitch;
	var yaw = e.Yaw;
	console.log('pitch: ' + pitch + ', yaw: '+ yaw);
	if(document.getElementById('pitch-value'))
	    document.getElementById('pitch-value').innerHTML = pitch.toString();
    if(document.getElementById('yaw-value'))
	    document.getElementById('yaw-value').innerHTML = yaw.toString();
    if(document.getElementsByName('pitch-value-submit').length)
        document.getElementsByName('pitch-value-submit')[0].value = pitch;
    if(document.getElementsByName('yaw-value-submit').length)
        document.getElementsByName('yaw-value-submit')[0].value = yaw;
    var newEnding = '/'+currentVrViewId+'/'+yaw.toString()+'/'+pitch.toString();
    if(document.getElementById('dynamic-button-add-existing'))
        document.getElementById('dynamic-button-add-existing').setAttribute('href', linkAddExisting + newEnding);
    if(document.getElementById('dynamic-button-add-new'))
        document.getElementById('dynamic-button-add-new').setAttribute('href', linkAddNew + newEnding);
    if(document.getElementById('dynamic-button-default-yaw'))
        document.getElementById('dynamic-button-default-yaw').setAttribute('href', linkDefaultYaw + '/'+currentVrViewId+'/'+yaw.toString());
    var hotspotLinks = document.getElementsByClassName('dynamic-button-hotspot-position');
    for(var i = 0; i < hotspotLinks.length; i++) {
        var hotspot_id = hotspotLinks[i].getAttribute('hotspot');
        hotspotLinks[i].setAttribute('href', linkHotspotPosition + '/' + hotspot_id + newEnding);
    }
}

function onModeChange(e) {
    console.log('onModeChange', e.mode);
}

function onVRViewError(e) {
    console.log('Error! %s', e.message);
}

jQuery(document).ready(
    function () {
        onLoad();
    }
);