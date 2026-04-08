"use strict";
const renderCanvas = document.getElementById('renderCanvas');
const babylonEngine = new BABYLON.Engine(renderCanvas, true, { preserveDrawingBuffer: true, stencil: true });
class SceneManager {
    static create(engine, canvas) {
        const s = new BABYLON.Scene(engine);
        let CAMERA;
        CAMERA = new BABYLON.UniversalCamera("Camera", new BABYLON.Vector3(300, 1500, -50), s);
        CAMERA.setTarget(CAMERA.position.add(new BABYLON.Vector3(0, -10, 0)));
        CAMERA.attachControl(canvas, true);
        s.gravity = new BABYLON.Vector3(0, -3.81, 0);
        CAMERA.applyGravity = true;
        CAMERA.needMoveForGravity = true;
        s.collisionsEnabled = true;
        CAMERA.checkCollisions = true;
        CAMERA.ellipsoid = new BABYLON.Vector3(0.6, 3, 0.6);
        CAMERA.ellipsoidOffset = new BABYLON.Vector3(0, 0, 0);
        CAMERA.speed = 1;
        CAMERA.angularSensibility = 1000;
        CAMERA.inertia = 0.8;
        CAMERA.keysUp[0] = "W".charCodeAt(0);
        CAMERA.keysDown[0] = "S".charCodeAt(0);
        CAMERA.keysLeft[0] = "A".charCodeAt(0);
        CAMERA.keysRight[0] = "D".charCodeAt(0);
        CAMERA.keysRotateLeft[0] = 37;
        CAMERA.keysRotateRight[0] = 39;
        CAMERA.keysRotateUp[0] = 3;
        CAMERA.keysRotateDown[0] = 3;
        const pipeline = new BABYLON.DefaultRenderingPipeline("pipeline", true, s, [CAMERA]);
        pipeline.imageProcessingEnabled = true;
        pipeline.imageProcessing.vignetteEnabled = false;
        pipeline.imageProcessing.exposure = 1;
        pipeline.imageProcessing.contrast = 1;
        pipeline.imageProcessing.colorCurvesEnabled = true;
        const sun = new BABYLON.DirectionalLight("sun", new BABYLON.Vector3(-1, -2, -1), s);
        sun.position = new BABYLON.Vector3(20, 100, 20);
        sun.intensity = 2.5;
        sun.diffuse = new BABYLON.Color3(1, 1, 0.9);
        const hemi = new BABYLON.HemisphericLight('hemi', new BABYLON.Vector3(0, 1, 0.5), s);
        hemi.intensity = 3;
        s.clearColor = new BABYLON.Color4(0.4, 0.7, 1, 1);
        return s;
    }
    static run(scene, engine) {
        engine.runRenderLoop(() => scene.render());
        window.addEventListener('resize', () => engine.resize());
        scene.onBeforeRenderObservable.add(() => {
            const sausageHitbox = scene.getMeshByName("item");
            const stoveMesh = scene.getMeshByName("stove");
            if (sausageHitbox && stoveMesh) {
                scene.meshes.filter(m => m.name === "item").forEach(sausageHitbox => {
                    if (sausageHitbox.intersectsMesh(stove, false)) {
                        createStoveSmoke(scene, stove);
                    }
                });
            }
        });
    }
    static enablePointerLock(scene, canvas) {
        let isLocked = false;
        scene.onPointerDown = function (_evt) {
            if (!isLocked && canvas.requestPointerLock) {
                canvas.requestPointerLock();
            }
        };
    }
    static axes() {
        const x = BABYLON.MeshBuilder.CreateLines("x", { points: [new BABYLON.Vector3(0, 0, 0), new BABYLON.Vector3(1000, 0, 0)] }, sceneInstance);
        x.color = new BABYLON.Color3(1, 0, 0);
        const y = BABYLON.MeshBuilder.CreateLines("y", { points: [new BABYLON.Vector3(0, 0, 0), new BABYLON.Vector3(0, 1000, 0)] }, sceneInstance);
        y.color = new BABYLON.Color3(0, 1, 0);
        const z = BABYLON.MeshBuilder.CreateLines("z", { points: [new BABYLON.Vector3(0, 0, 0), new BABYLON.Vector3(0, 0, 1000)] }, sceneInstance);
        z.color = new BABYLON.Color3(0, 0, 1);
    }
    static createMap(scene) {
        BABYLON.SceneLoader.ImportMesh("", "Assets/", "BrokenBonesMapV3.gltf", scene, (meshes) => {
            const allMeshes = meshes.filter(m => m instanceof BABYLON.Mesh);
            const mergedCity = BABYLON.Mesh.MergeMeshes(allMeshes, true, true, undefined, false, true);
            if (mergedCity) {
                mergedCity.name = "OptimizedMap";
                mergedCity.checkCollisions = true;
                mergedCity.freezeWorldMatrix();
                cons.getBoundingInfo();
                const center = boundingInfo.boundingBox.centerWorld;
                const size = boundingInfo.boundingBox.extendSizeWorld;
                if (size.x === 0 && size.y === 0 && size.z === 0) {
                    camera.position = new BABYLON.Vector3(50, 50, 50);
                    camera.setTarget(new BABYLON.Vector3(0, 0, 0));
                }
                else {
                    const maxDim = Math.max(size.x, size.y, size.z);
                    camera.position = center.add(new BABYLON.Vector3(maxDim * 2, maxDim * 1.5, maxDim * 2));
                    camera.setTarget(center);
                    camera.maxZ = maxDim * 10;
                }
            }
        });
    }
}
const sceneInstance = SceneManager.create(babylonEngine, renderCanvas);
let ACTIVE_MESH;
const scene = SceneManager.create(babylonEngine, renderCanvas);
SceneManager.run(scene, babylonEngine);
const camera = scene.activeCamera;
SceneManager.createMap(scene);
// Flugmodus mit Shift+P
let flightMode = false;
window.addEventListener("keydown", (e) => {
    if (e.shiftKey && e.key.toLowerCase() === "p") {
        flightMode = !flightMode;
        if (flightMode) {
            console.log("Flugmodus aktiviert");
            scene.gravity = new BABYLON.Vector3(0, 0, 0);
            camera.applyGravity = false;
            camera.needMoveForGravity = false;
            camera.speed = 10;
        }
        else {
            console.log("Normaler Modus");
            scene.gravity = new BABYLON.Vector3(0, -3.81, 0);
            camera.applyGravity = true;
            camera.needMoveForGravity = true;
            camera.speed = 1;
        }
    }
});
// SceneManager.createFloor(scene);
SceneManager.enablePointerLock(scene, renderCanvas);
const stove = BABYLON.MeshBuilder.CreateBox("stove", { width: 7, height: 1, depth: 3 }, scene);
stove.position = new BABYLON.Vector3(4, 2.8, 4);
stove.checkCollisions = true;
stove.isPickable = false;
stove.visibility = 0;
const smokeSystem = new BABYLON.ParticleSystem("smoke", 200, scene);
function createStoveSmoke(scene, stove) {
    smokeSystem.particleTexture = new BABYLON.Texture("Assets/smoke.png", scene);
    smokeSystem.emitter = new BABYLON.Vector3(stove.position.x, stove.position.y + 1, stove.position.z);
    smokeSystem.minEmitBox = new BABYLON.Vector3(-1, 0, -1);
    smokeSystem.maxEmitBox = new BABYLON.Vector3(1, 0, 1);
    smokeSystem.color1 = new BABYLON.Color4(0.8, 0.8, 0.8, 0.6);
    smokeSystem.color2 = new BABYLON.Color4(0.9, 0.9, 0.9, 0.3);
    smokeSystem.colorDead = new BABYLON.Color4(0.8, 0.8, 0.8, 0);
    smokeSystem.minSize = 0.7;
    smokeSystem.maxSize = 1.5;
    smokeSystem.minLifeTime = 1.2;
    smokeSystem.maxLifeTime = 2.5;
    smokeSystem.emitRate = 15;
    smokeSystem.blendMode = BABYLON.ParticleSystem.BLENDMODE_STANDARD;
    smokeSystem.gravity = new BABYLON.Vector3(0, 0.1, 0);
    smokeSystem.direction1 = new BABYLON.Vector3(-0.2, 1, -0.2);
    smokeSystem.direction2 = new BABYLON.Vector3(0.2, 1, 0.2);
    smokeSystem.minAngularSpeed = 0;
    smokeSystem.maxAngularSpeed = Math.PI;
    smokeSystem.minEmitPower = 0.5;
    smokeSystem.maxEmitPower = 1.2;
    smokeSystem.updateSpeed = 0.02;
    smokeSystem.start();
    setTimeout(() => {
        smokeSystem.stop();
    }, 1000);
}
const interactable = BABYLON.MeshBuilder.CreateBox("interactableBox", { height: 7, width: 3.5, depth: 3.5 }, scene);
interactable.position = new BABYLON.Vector3(-16, 4.1, 3);
interactable.isPickable = true;
interactable.visibility = 0;
const popUp = document.getElementById("pop");
let isLooking = false;
let holdStart = null;
let opeen = false;
scene.onBeforeRenderObservable.add(() => {
    var _a;
    scene.meshes.filter(m => m.name === "item").forEach(sausageHitbox => {
        const stoveMesh = scene.getMeshByName("stove");
        if (stoveMesh &&
            sausageHitbox.intersectsMesh(stoveMesh, false) &&
            !sausageHitbox.isCooked &&
            !sausageHitbox.isCooking) {
            replaceWithHotdog(scene, sausageHitbox);
        }
    });
    const pick = scene.pick(scene.pointerX, scene.pointerY);
    const isHovering = (pick === null || pick === void 0 ? void 0 : pick.hit) && ((_a = pick.pickedMesh) === null || _a === void 0 ? void 0 : _a.name) === "interactableBox";
    if (isHovering && !isLooking && !opeen) {
        popUp.style.display = "block";
        popUp.innerText = "Hold [E]";
        isLooking = true;
    }
    else if (!isHovering && isLooking) {
        popUp.style.display = "none";
        holdStart = null;
        isLooking = false;
    }
    if (isLooking && holdStart) {
        popUp.innerText = "Opening...";
        const holdDuration = performance.now() - holdStart;
        if (holdDuration >= 2) {
            OpenFridge();
        }
        holdStart = null;
    }
});
window.addEventListener("keydown", (e) => {
    if (e.key.toLowerCase() === "e" && isLooking && !holdStart) {
        holdStart = performance.now();
    }
});
window.addEventListener("keyup", (e) => {
    if (e.key.toLowerCase() === "e") {
        holdStart = null;
        if (isLooking)
            popUp.innerText = "Hold [E]";
    }
});
let fridge = document.getElementById("fridge");
fridge.style.display = "none";
function OpenFridge() {
    let fridge = document.getElementById("fridge");
    fridge.style.display = "block";
    fridge.style.justifyContent = "center";
    fridge.style.alignContent = "center";
    popUp.style.display = "none";
    opeen = true;
}
function replaceWithHotdog(scene, sausageHitbox) {
    sausageHitbox.isCooked = true;
    setTimeout(() => {
        sausageHitbox.getChildMeshes().forEach(child => child.dispose());
        BABYLON.SceneLoader.ImportMesh("", "Assets/", "hotdog.glb", scene, (meshes) => {
            const hotdog = meshes[0];
            hotdog.scaling.scaleInPlace(3);
            hotdog.parent = sausageHitbox;
            hotdog.position = BABYLON.Vector3.Zero();
            hotdog.isPickable = false;
            hotdog.name = "hotdog";
            sausageHitbox.isCooked = true;
        });
    }, 10000);
}
function spawnCustomer(scene) {
    BABYLON.SceneLoader.ImportMesh("", "Assets/", "noob.glb", scene, (meshes) => {
        const parent = new BABYLON.TransformNode("customerParent", scene);
        meshes.forEach(m => m.parent = parent);
        parent.scaling.scaleInPlace(1.2);
        parent.position = new BABYLON.Vector3(-1.5, 0, -10);
        parent.rotation.y = Math.PI / 2;
        parent.rotation.x = 4.72;
        const cloud = createOrderCloud(scene, parent, "Can i get a hotdog and a coke?");
        parent.orderCloud = cloud;
    });
}
function updateOrderCloud(parentMesh, newOrder) {
    const cloud = parentMesh.orderCloud;
    if (cloud && cloud.dynamicTexture) {
        cloud.dynamicTexture.getContext().clearRect(0, 0, 256, 128);
        cloud.dynamicTexture.drawText(newOrder, 20, 80, "bold 32px Arial", "black", "white", true);
        cloud.dynamicTexture.update();
    }
}
function spawnMoney(scene) {
    BABYLON.SceneLoader.ImportMesh("", "Assets/", "money.glb", scene, (meshes) => {
        const model = meshes[0];
        model.scaling.scaleInPlace(0.3);
        model.isPickable = false;
        model.computeWorldMatrix(true);
        const boundingInfo = model.getBoundingInfo();
        const size = boundingInfo.boundingBox.extendSizeWorld.scale(0.2);
        const hitbox = BABYLON.MeshBuilder.CreateBox("item", {
            width: size.x * 1.2,
            height: size.y * 1.2,
            depth: size.z * 1.2
        }, scene);
        hitbox.position = new BABYLON.Vector3(-4.5, 3.6, -7.5);
        hitbox.isPickable = true;
        hitbox.visibility = 0;
        hitbox.checkCollisions = true;
        model.parent = hitbox;
        model.position = BABYLON.Vector3.Zero();
        model.isPickable = false;
        model.name = "money";
    });
}
spawnMoney(scene);
function createOrderCloud(scene, parentMesh, orderText) {
    const dynamicTexture = new BABYLON.DynamicTexture("orderCloudTexture", { width: 256, height: 128 }, scene, false);
    dynamicTexture.drawText(orderText, 20, 80, "bold 14px Arial", "black", "white", true);
    const cloud = BABYLON.MeshBuilder.CreatePlane("orderCloud", { width: 2.5, height: 1 }, scene);
    cloud.position = new BABYLON.Vector3(0, 5, 0);
    cloud.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
    const mat = new BABYLON.StandardMaterial("orderCloudMat", scene);
    mat.diffuseTexture = dynamicTexture;
    mat.emissiveColor = new BABYLON.Color3(1, 1, 1);
    mat.backFaceCulling = false;
    cloud.material = mat;
    cloud.parent = parentMesh;
    cloud.dynamicTexture = dynamicTexture;
    return cloud;
}
spawnCustomer(scene);
function checkOrderCollision(scene, orderBox) {
    orderBox.checkCollisions = true;
    const hotdog = scene.meshes.find(m => m.name === "item" && m.getChildMeshes().some(child => child.name === "hotdog"));
    const coke = scene.meshes.find(m => m.name === "item" && m.getChildMeshes().some(child => child.name === "coke"));
    if (hotdog) {
        hotdog.checkCollisions = true;
    }
    if (coke) {
        coke.checkCollisions = true;
    }
    if (hotdog && coke &&
        orderBox.intersectsMesh(hotdog, false) &&
        orderBox.intersectsMesh(coke, false)) {
        spawnMoney(scene);
        hotdog.dispose();
        coke.dispose();
        return true;
    }
    return false;
}
const orderBox = BABYLON.MeshBuilder.CreateBox("orderBox", { width: 3, height: 3, depth: 3 }, scene);
orderBox.position = new BABYLON.Vector3(-3.9, 4, -7.5);
orderBox.visibility = 0.2;
orderBox.isPickable = false;
orderBox.checkCollisions = true;
scene.onBeforeRenderObservable.add(() => {
    checkOrderCollision(scene, orderBox);
});
function spawnSausage(scene) {
    BABYLON.SceneLoader.ImportMesh("", "Assets/", "sausage.glb", scene, (meshes) => {
        const model = meshes[0];
        model.scaling.scaleInPlace(3);
        model.computeWorldMatrix(true);
        const boundingInfo = model.getBoundingInfo();
        const size = boundingInfo.boundingBox.extendSizeWorld.scale(2);
        const hitbox = BABYLON.MeshBuilder.CreateBox("item", {
            width: size.x * 1.2,
            height: size.y * 1.2,
            depth: size.z * 1.2
        }, scene);
        hitbox.position = new BABYLON.Vector3(0, 4, 0);
        hitbox.isPickable = true;
        hitbox.visibility = 0;
        hitbox.checkCollisions = true;
        model.parent = hitbox;
        model.position = BABYLON.Vector3.Zero();
        model.isPickable = false;
    });
}
function spawnCoke(scene) {
    BABYLON.SceneLoader.ImportMesh("", "Assets/", "coke.glb", scene, (meshes) => {
        const model = meshes[0];
        model.scaling.scaleInPlace(3);
        model.computeWorldMatrix(true);
        const boundingInfo = model.getBoundingInfo();
        const size = boundingInfo.boundingBox.extendSizeWorld.scale(2);
        const hitbox = BABYLON.MeshBuilder.CreateBox("item", {
            width: size.x * 1.2,
            height: size.y * 1.2,
            depth: size.z * 1.2
        }, scene);
        hitbox.position = new BABYLON.Vector3(0, 4, 0);
        hitbox.isPickable = true;
        hitbox.visibility = 0;
        hitbox.checkCollisions = true;
        model.parent = hitbox;
        model.position = new BABYLON.Vector3(0, -1, 0);
        model.isPickable = false;
        model.name = "coke";
        hitbox.name = "item";
    });
}
let pickedMesh = null;
let isDragging = false;
let dragOffset = new BABYLON.Vector3();
let dragStartDistance = 0;
scene.onPointerDown = () => {
    var _a;
    if (renderCanvas.requestPointerLock) {
        renderCanvas.requestPointerLock();
    }
    const ray = scene.activeCamera.getForwardRay();
    const pick = scene.pickWithRay(ray);
    if ((pick === null || pick === void 0 ? void 0 : pick.hit) && ((_a = pick.pickedMesh) === null || _a === void 0 ? void 0 : _a.name) === "item") {
        pickedMesh = pick.pickedMesh;
        isDragging = true;
        pickedMesh.checkCollisions = false;
        dragStartDistance = BABYLON.Vector3.Distance(ray.origin, pickedMesh.position);
        const pickPoint = pick.pickedPoint;
        dragOffset = pickedMesh.position.subtract(pickPoint);
    }
};
scene.onBeforeRenderObservable.add(() => {
    var _a;
    const ray = scene.activeCamera.getForwardRay();
    const pick = scene.pickWithRay(ray);
    const isHovering = (pick === null || pick === void 0 ? void 0 : pick.hit) && ((_a = pick.pickedMesh) === null || _a === void 0 ? void 0 : _a.name) === "interactableBox";
    if (isHovering && !isLooking && !opeen) {
        popUp.style.display = "block";
        popUp.innerText = "Hold [E]";
        isLooking = true;
    }
    else if (!isHovering && isLooking) {
        popUp.style.display = "none";
        holdStart = null;
        isLooking = false;
    }
    if (isLooking && holdStart) {
        popUp.innerText = "Opening...";
        const holdDuration = performance.now() - holdStart;
        if (holdDuration >= 2) {
            OpenFridge();
        }
        holdStart = null;
    }
});
scene.onPointerMove = () => {
    if (!isDragging || !pickedMesh)
        return;
    const ray = scene.activeCamera.getForwardRay();
    const pointInFront = ray.origin.add(ray.direction.scale(dragStartDistance));
    pickedMesh.position = pointInFront.add(dragOffset);
    pickedMesh.position.y = Math.max(pickedMesh.position.y, 0.1);
};
scene.onPointerUp = () => {
    if (pickedMesh) {
        pickedMesh.checkCollisions = true;
    }
    isDragging = false;
    pickedMesh = null;
};
const walletBox = BABYLON.MeshBuilder.CreateBox("walletBox", { width: 2, height: 1, depth: 3 }, scene);
walletBox.position = new BABYLON.Vector3(-5, 3, 4);
walletBox.visibility = 0.5;
walletBox.isPickable = false;
walletBox.checkCollisions = true;
let balance = 0;
scene.onBeforeRenderObservable.add(() => {
    const monies = scene.meshes.filter(m => m.name === "item" && m.getChildMeshes().some(child => child.name === "money"));
    monies.forEach(money => {
        if (walletBox.intersectsMesh(money, false)) {
            balance += 1;
            document.getElementById("balance").innerText = `Balance: $${balance}`;
            money.dispose();
        }
    });
});
function createInvisibleBorder(scene, width, depth, height = 10) {
    const wallL = BABYLON.MeshBuilder.CreateBox("borderL", { width: 1, height, depth }, scene);
    wallL.position = new BABYLON.Vector3(-width / 2, height / 2, 0);
    wallL.visibility = 0;
    wallL.isPickable = false;
    wallL.checkCollisions = true;
    const wallR = BABYLON.MeshBuilder.CreateBox("borderR", { width: 1, height, depth }, scene);
    wallR.position = new BABYLON.Vector3(width / 2, height / 2, 0);
    wallR.visibility = 0;
    wallR.isPickable = false;
    wallR.checkCollisions = true;
    const wallF = BABYLON.MeshBuilder.CreateBox("borderF", { width, height, depth: 1 }, scene);
    wallF.position = new BABYLON.Vector3(0, height / 2, -depth / 2);
    wallF.visibility = 0;
    wallF.isPickable = false;
    wallF.checkCollisions = true;
    const wallB = BABYLON.MeshBuilder.CreateBox("borderB", { width, height, depth: 1 }, scene);
    wallB.position = new BABYLON.Vector3(0, height / 2, depth / 2);
    wallB.visibility = 0;
    wallB.isPickable = false;
    wallB.checkCollisions = true;
}
createInvisibleBorder(scene, 30, 30);
