
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

function setSourceParams() {
    mode = drupalSettings.vr_view.mode;
    startImage = drupalSettings.vr_view.start_image;
    startView = drupalSettings.vr_view.start_view;
    views = drupalSettings.vr_view.views;
    linkAddExisting = drupalSettings.vr_view.link_add_existing;
    linkAddNew = drupalSettings.vr_view.link_add_new;
    linkDefaultYaw = drupalSettings.vr_view.link_default_yaw;
    currentVrViewId = views[startView]['id'];
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
    vrView.on('hover', onVRViewHover);
}

function onVRViewReady(e) {
    console.log('onVRViewReady');
    loadScene(startView);
}

function onVRViewHover(e) {
    console.log('onVRViewClick', e);
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
    if(mode !== modes.typeAdmin)
        defaultYaw = defaultYawReal;
    else
        defaultYaw = 0;
    var newEnding = '/'+currentVrViewId+'/'+defaultYaw+'/0';
    document.getElementById('dynamic-button-add-existing').setAttribute('href', linkAddExisting + newEnding);
    document.getElementById('dynamic-button-add-new').setAttribute('href', linkAddNew + newEnding);
    document.getElementById('dynamic-button-default-yaw').setAttribute('href', linkDefaultYaw + '/'+currentVrViewId + '/' + defaultYaw);
    document.getElementById('default-yaw-value').innerHTML = defaultYawReal.toString();
    document.getElementById('vrview-title').innerHTML = views[id]['name'];
    document.getElementById('vrview-description').innerHTML = views[id]['description'];
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
	document.getElementById('pitch-value').innerHTML = pitch.toString();
    document.getElementById('yaw-value').innerHTML = yaw.toString();
    document.getElementsByName('pitch-value-submit')[0].value = pitch;
    document.getElementsByName('yaw-value-submit')[0].value = yaw;
    var newEnding = '/'+currentVrViewId+'/'+yaw.toString()+'/'+pitch.toString();
    document.getElementById('dynamic-button-add-existing').setAttribute('href', linkAddExisting + newEnding);
    document.getElementById('dynamic-button-add-new').setAttribute('href', linkAddNew + newEnding);
    document.getElementById('dynamic-button-default-yaw').setAttribute('href', linkDefaultYaw + '/'+currentVrViewId+'/'+yaw.toString());
}

function onModeChange(e) {
    console.log('onModeChange', e.mode);
}

function onVRViewError(e) {
    console.log('Error! %s', e.message);
}

jQuery(document).ready(
    function ($) {
        onLoad();
    }
);