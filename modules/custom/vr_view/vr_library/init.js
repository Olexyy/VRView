/*
 * Copyright 2016 Google Inc. All Rights Reserved.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
var vrView;
var startView;
var startImage;
var views;
var linkAddExisting;
var linkAddNew;

function setSourceParams() {
    startImage = drupalSettings.vr_view.start_image;
    startView = drupalSettings.vr_view.start_view;
    views = drupalSettings.vr_view.views;
    linkAddExisting = drupalSettings.vr_view.link_add_existing;
    linkAddNew = drupalSettings.vr_view.link_add_new;
}

function onLoad() {
    setSourceParams();
    vrView = new VRView.Player('#vrview', {
    width: '100%',
    height: 480,
    image: startImage,
    preview: startImage,
    is_stereo: false,
    is_autopan_off: true
    });
    vrView.on('ready', onVRViewReady);
    vrView.on('modechange', onModeChange);
    vrView.on('error', onVRViewError);
    vrView.on('click', onVRViewClick);
    vrView.on('getposition', onVRViewPosition);
}

function onVRViewReady(e) {
    console.log('onVRViewReady');
    loadScene(startView);
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
    vrView.setContent({
        image: views[id]['source'],
        preview: views[id]['source'],
        is_stereo: views[id]['is_stereo'],
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
	console.log('pitch:' + pitch + ', yaw'+ yaw);
	document.getElementById('pitch-value').innerHTML = pitch.toString();
    document.getElementById('yaw-value').innerHTML = yaw.toString();
    document.getElementsByName('pitch-value-submit')[0].value = pitch;
    document.getElementsByName('yaw-value-submit')[0].value = yaw;
    var newEnding = '/'+yaw.toString()+'/'+pitch.toString();
    document.getElementById('dynamic-button-add-existing').setAttribute('href', linkAddExisting + newEnding);
    document.getElementById('dynamic-button-add-new').setAttribute('href', linkAddNew + newEnding);
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
//window.addEventListener('load', onLoad);
