
const renderCanvas = document.getElementById('renderCanvas') as HTMLCanvasElement;
const babylonEngine = new BABYLON.Engine(renderCanvas, true, { preserveDrawingBuffer: true, stencil: true });

class SceneManager {
    static create(engine: BABYLON.Engine, canvas: HTMLCanvasElement): BABYLON.Scene {
        const s = new BABYLON.Scene(engine);
        let CAMERA: BABYLON.UniversalCamera;
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
        pipeline.imageProcessing.colorCurvesEnabled = true

        const sun = new BABYLON.DirectionalLight("sun", new BABYLON.Vector3(-1, -2, -1), s);
        sun.position = new BABYLON.Vector3(20, 100, 20);
        sun.intensity = 2.5;
        sun.diffuse = new BABYLON.Color3(1, 1, 0.9);

        const hemi = new BABYLON.HemisphericLight('hemi', new BABYLON.Vector3(0, 1, 0.5), s);
        hemi.intensity = 3;
        s.clearColor = new BABYLON.Color4(0.4, 0.7, 1, 1);
        return s;
    }
     

    static run(scene: BABYLON.Scene, engine: BABYLON.Engine) {
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

    static enablePointerLock(scene: BABYLON.Scene, canvas: HTMLCanvasElement) {
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
static createMap(scene: BABYLON.Scene) {
        BABYLON.SceneLoader.ImportMesh("", "Assets/", "BrokenBonesMapV1 .gltf", scene, (meshes) => {
            const allMeshes = meshes.filter(m => m instanceof BABYLON.Mesh) as BABYLON.Mesh[];
            const mergedCity = BABYLON.Mesh.MergeMeshes(allMeshes, true, true, undefined, false, true);

            if (mergedCity) {
                mergedCity.name = "OptimizedMap";
                mergedCity.checkCollisions = true;
                mergedCity.freezeWorldMatrix();
                
                const boundingInfo = mergedCity.getBoundingInfo();
                const center = boundingInfo.boundingBox.centerWorld;
                const size = boundingInfo.boundingBox.extendSizeWorld;
                
                if (size.x === 0 && size.y === 0 && size.z === 0) {
                    camera.position = new BABYLON.Vector3(50, 50, 50);
                    camera.setTarget(new BABYLON.Vector3(0, 0, 0));
                } else {
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
let ACTIVE_MESH: BABYLON.AbstractMesh[];

const scene = SceneManager.create(babylonEngine, renderCanvas)
SceneManager.run(scene, babylonEngine);

const camera = scene.activeCamera as BABYLON.UniversalCamera;
SceneManager.createMap(scene)
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
        } else {
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
function createStoveSmoke(scene: BABYLON.Scene, stove: BABYLON.Mesh) {
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
const popUp = document.getElementById("pop")!;
let isLooking = false;
let holdStart: number | null = null;
let opeen = false;
scene.onBeforeRenderObservable.add(() => {
    scene.meshes.filter(m => m.name === "item").forEach(sausageHitbox => {
        const stoveMesh = scene.getMeshByName("stove");
        if (
            stoveMesh &&
            sausageHitbox.intersectsMesh(stoveMesh, false) &&
            !(sausageHitbox as any).isCooked &&
            !(sausageHitbox as any).isCooking
        ) {
            replaceWithHotdog(scene, sausageHitbox);
        }
    });

    const pick = scene.pick(scene.pointerX, scene.pointerY);
    const isHovering = pick?.hit && pick.pickedMesh?.name === "interactableBox";

    if (isHovering && !isLooking && !opeen) {
        popUp.style.display = "block";
        popUp.innerText = "Hold [E]";
        isLooking = true;
    } else if (!isHovering && isLooking) {
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
        if (isLooking) popUp.innerText = "Hold [E]";
    }
});

let fridge = document.getElementById("fridge")!;
fridge.style.display = "none";
function OpenFridge() {
    let fridge = document.getElementById("fridge")!;
    fridge.style.display = "block";
    fridge.style.justifyContent = "center";
    fridge.style.alignContent = "center";
    popUp.style.display = "none";
    opeen = true;
}
function replaceWithHotdog(scene: BABYLON.Scene, sausageHitbox: BABYLON.AbstractMesh) {
    (sausageHitbox as any).isCooked = true;
    setTimeout(() => {
        sausageHitbox.getChildMeshes().forEach(child => child.dispose());
        BABYLON.SceneLoader.ImportMesh("", "Assets/", "hotdog.glb", scene, (meshes) => {
            const hotdog = meshes[0];
            hotdog.scaling.scaleInPlace(3);
            hotdog.parent = sausageHitbox;
            hotdog.position = BABYLON.Vector3.Zero();
            hotdog.isPickable = false;
            hotdog.name = "hotdog";
            (sausageHitbox as any).isCooked = true;
        });
    }, 10000); 
}
function spawnCustomer(scene: BABYLON.Scene) {
BABYLON.SceneLoader.ImportMesh("", "Assets/", "noob.glb", scene, (meshes) => {
    const parent = new BABYLON.TransformNode("customerParent", scene);
    meshes.forEach(m => m.parent = parent);
    parent.scaling.scaleInPlace(1.2);
    parent.position = new BABYLON.Vector3(-1.5, 0, -10);
   
    parent.rotation.y = Math.PI /2 ; 
    parent.rotation.x = 4.72 ; 
    const cloud = createOrderCloud(scene, parent, "Can i get a hotdog and a coke?");

        
        (parent as any).orderCloud = cloud;

    
});
}
function updateOrderCloud(parentMesh: BABYLON.Node, newOrder: string) {
    const cloud = (parentMesh as any).orderCloud as BABYLON.Mesh;
    if (cloud && (cloud as any).dynamicTexture) {
        (cloud as any).dynamicTexture.getContext().clearRect(0, 0, 256, 128);
        (cloud as any).dynamicTexture.drawText(newOrder, 20, 80, "bold 32px Arial", "black", "white", true);
        (cloud as any).dynamicTexture.update();
    }
}
function spawnMoney(scene: BABYLON.Scene) {
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


function createOrderCloud(scene: BABYLON.Scene, parentMesh: BABYLON.Node, orderText: string): BABYLON.Mesh {
    const dynamicTexture = new BABYLON.DynamicTexture("orderCloudTexture", {width:256, height:128}, scene, false);
    dynamicTexture.drawText(orderText, 20, 80, "bold 14px Arial", "black", "white", true);
    const cloud = BABYLON.MeshBuilder.CreatePlane("orderCloud", {width:2.5, height:1}, scene);
    cloud.position = new BABYLON.Vector3(0, 5, 0); 
    cloud.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL; 
    const mat = new BABYLON.StandardMaterial("orderCloudMat", scene);
    mat.diffuseTexture = dynamicTexture;
    mat.emissiveColor = new BABYLON.Color3(1, 1, 1);
    mat.backFaceCulling = false;
    cloud.material = mat;
    cloud.parent = parentMesh;
    (cloud as any).dynamicTexture = dynamicTexture;
    return cloud;
}
spawnCustomer(scene);
function checkOrderCollision(scene: BABYLON.Scene, orderBox: BABYLON.Mesh) {
    orderBox.checkCollisions = true;
    const hotdog = scene.meshes.find(m => m.name === "item" && m.getChildMeshes().some(child => child.name === "hotdog"));
    const coke = scene.meshes.find(m => m.name === "item" && m.getChildMeshes().some(child => child.name === "coke"));
    if (hotdog) {
        hotdog.checkCollisions = true;
    }
    if (coke) {
        coke.checkCollisions = true;
    }
    if (
        hotdog && coke &&
        orderBox.intersectsMesh(hotdog, false) &&
        orderBox.intersectsMesh(coke, false)
    ) {
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
function spawnSausage(scene: BABYLON.Scene) {
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
function spawnCoke(scene: BABYLON.Scene) {
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

let pickedMesh: BABYLON.Mesh | null = null;
let isDragging = false;
let dragOffset = new BABYLON.Vector3();
let dragStartDistance = 0;

scene.onPointerDown = () => {
    if (renderCanvas.requestPointerLock) {
        renderCanvas.requestPointerLock();
    }
    const ray = scene.activeCamera!.getForwardRay();
    const pick = scene.pickWithRay(ray);
    if (pick?.hit && pick.pickedMesh?.name === "item") {
        pickedMesh = pick.pickedMesh as BABYLON.Mesh;
        isDragging = true;
        pickedMesh.checkCollisions = false;

        dragStartDistance = BABYLON.Vector3.Distance(ray.origin, pickedMesh.position);

        const pickPoint = pick.pickedPoint!;
        dragOffset = pickedMesh.position.subtract(pickPoint);
    }
};

scene.onBeforeRenderObservable.add(() => {
    const ray = scene.activeCamera!.getForwardRay();
    const pick = scene.pickWithRay(ray);
    const isHovering = pick?.hit && pick.pickedMesh?.name === "interactableBox";

    if (isHovering && !isLooking && !opeen) {
        popUp.style.display = "block";
        popUp.innerText = "Hold [E]";
        isLooking = true;
    } else if (!isHovering && isLooking) {
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
    if (!isDragging || !pickedMesh) return;
    const ray = scene.activeCamera!.getForwardRay();
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

let balance = (window as any).INITIAL_BALANCE || 0;
let moneyMultiplier = parseFloat(localStorage.getItem('moneyMultiplier') || '1');
let speedMultiplier = parseFloat(localStorage.getItem('speedMultiplier') || '1');
let overallMultiplier = parseFloat(localStorage.getItem('overallMultiplier') || '1');

// fetch authoritative player data from server (balance + multipliers)
fetch('./main.php', { method: 'POST', body: (()=>{ const f=new FormData(); f.append('action','get_player'); return f; })() })
    .then(r=>r.json()).then(data=>{
        if (data && data.success) {
            balance = data.balance;
            moneyMultiplier = data.moneyMultiplier ?? moneyMultiplier;
            speedMultiplier = data.speedMultiplier ?? speedMultiplier;
            overallMultiplier = data.overallMultiplier ?? overallMultiplier;
            localStorage.setItem('moneyMultiplier', String(moneyMultiplier));
            localStorage.setItem('speedMultiplier', String(speedMultiplier));
            localStorage.setItem('overallMultiplier', String(overallMultiplier));
            const el = document.getElementById('balance-amount'); if (el) el.innerText = String(balance);
        }
    }).catch(()=>{});

function awardMoney(amount: number) {
    amount = Math.max(0, Math.floor(amount));
    if (amount === 0) return;
    // apply multipliers client-side then update server
    const finalAmount = Math.max(0, Math.floor(amount * moneyMultiplier * overallMultiplier));
    if (finalAmount === 0) return;
    const fd = new FormData(); fd.append('action','add_money'); fd.append('amount', String(finalAmount));
    fetch('./main.php', { method: 'POST', body: fd }).then(r=>r.json()).then(data=>{
        if (data.success) {
            balance = data.balance;
            const el = document.getElementById('balance-amount');
            if (el) el.innerText = String(balance);
        }
    }).catch(()=>{
        // fallback local
        balance += amount;
        const el = document.getElementById('balance-amount');
        if (el) el.innerText = String(balance);
    });
}

scene.onBeforeRenderObservable.add(() => {
    const monies = scene.meshes.filter(m =>
        m.name === "item" && m.getChildMeshes().some(child => child.name === "money")
    );

    monies.forEach(money => {
        if (walletBox.intersectsMesh(money, false)) {
            const base = 1;
            const amount = Math.max(1, Math.floor(base * moneyMultiplier * overallMultiplier));
            awardMoney(amount);
            money.dispose();
        }
    });
});

function createInvisibleBorder(scene: BABYLON.Scene, width: number, depth: number, height: number = 10) {
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

// Ragdoll simulation (simple velocity integration, camera follows ragdoll)
let ragdollMode = false;
let ragdollPos = camera.position.clone();
let ragdollVel = new BABYLON.Vector3(0,0,0);
const lastCollisionAt = new Map<string, number>();
let _savedCameraSpeed: number | null = null;

function nowMs(){ return performance.now(); }

window.addEventListener('keydown', (e)=>{
    if (e.key.toLowerCase() === 'r') {
        ragdollMode = !ragdollMode;
        if (ragdollMode) {
            // launch ragdoll from camera forward
            ragdollPos = camera.position.clone();
            const forward = camera.getDirection(new BABYLON.Vector3(0,0,1)).normalize();
            ragdollVel = forward.scale(10 * speedMultiplier).add(new BABYLON.Vector3(0, 2, 0));
            // fully disable camera inputs while ragdolling
            try { camera.detachControl(renderCanvas); } catch(e) {}
            try { _savedCameraSpeed = camera.speed; camera.speed = 0; } catch(e) {}
        } else {
            // stop ragdoll and reattach camera controls
            try { camera.attachControl(renderCanvas, true); } catch(e) {}
            try { if (_savedCameraSpeed !== null) camera.speed = _savedCameraSpeed; _savedCameraSpeed = null; } catch(e) {}
        }
    }
});

scene.onBeforeRenderObservable.add(() => {
    if (!ragdollMode) return;
    // integrate with slope sliding and friction
    const dt = (babylonEngine && typeof babylonEngine.getDeltaTime === 'function' ? babylonEngine.getDeltaTime() : 16) / 1000 || 1/60;
    // constant gravity
    const gravity = new BABYLON.Vector3(0, -9.81, 0);
    // apply gravity
    ragdollVel.addInPlace(gravity.scale(dt));

    // ground check: cast a short ray downward to detect ground and its normal
    const downRay = new BABYLON.Ray(ragdollPos.add(new BABYLON.Vector3(0, 0.2, 0)), new BABYLON.Vector3(0, -1, 0), 1.5);
    const pick = scene.pickWithRay(downRay);
    let grounded = false;
    let groundNormal = new BABYLON.Vector3(0,1,0);
    if (pick && pick.hit && pick.pickedPoint) {
        grounded = true;
        try {
            const n = (pick as any).getNormal && (pick as any).getNormal(true);
            if (n) groundNormal = n.normalize();
        } catch(e) {}
    }

    if (grounded) {
        // decompose velocity into normal and tangent
        const v = ragdollVel.clone();
        const vNormalComp = groundNormal.scale(BABYLON.Vector3.Dot(v, groundNormal));
        let vTangent = v.subtract(vNormalComp);

        // slope downhill direction: project gravity onto tangent plane
        const g = gravity.normalize();
        let downhill = g.subtract(groundNormal.scale(BABYLON.Vector3.Dot(g, groundNormal)));
        if (downhill.lengthSquared() > 0.0001) downhill = downhill.normalize();

        // slide acceleration along downhill proportional to slope
        const slopeAngle = Math.acos(Math.max(-1, Math.min(1, BABYLON.Vector3.Dot(groundNormal, new BABYLON.Vector3(0,1,0))))) || 0;
        const slideAccel = Math.sin(slopeAngle) * 9.81 * 0.6; // tuned factor
        // apply sliding accel to tangent
        vTangent = vTangent.add(downhill.scale(slideAccel * dt));

        // apply ground friction to tangent
        const friction = 4.0; // higher => quicker stop on flat ground
        vTangent = vTangent.scale(Math.max(0, 1 - friction * dt));

        // prevent sinking into ground: keep small upward normal if overlapping
        if (ragdollPos.y < pick.pickedPoint!.y + 0.05) {
            ragdollPos.y = pick.pickedPoint!.y + 0.05;
            // damp normal component
            vNormalComp.scaleInPlace(-0.2);
        }

        ragdollVel = vNormalComp.add(vTangent);
    }

    // integrate position
    ragdollPos = ragdollPos.add(ragdollVel.scale(dt));
    // keep above minimal ground if not grounded
    if (ragdollPos.y < 0.05) ragdollPos.y = 0.05;

    camera.position.copyFrom(ragdollPos);

    // check collisions with collidable meshes
    const collidables = scene.meshes.filter(m => m.checkCollisions && m.isVisible && m.isEnabled());
    for (const m of collidables) {
        if (!m.getBoundingInfo) continue;
        const key = m.name || m.uniqueId.toString();
        const last = lastCollisionAt.get(key) || 0;
        if (nowMs() - last < 300) continue; // cooldown per mesh
        const bbox = m.getBoundingInfo().boundingBox;
        const worldCenter = bbox.centerWorld;
        const dist = BABYLON.Vector3.Distance(worldCenter, ragdollPos);
        const threshold = Math.max(bbox.extendSizeWorld.length(), 1) + 1;
        if (dist < threshold) {
            const impactSpeed = ragdollVel.length();
            if (impactSpeed > 1.5) {
                const baseAmount = Math.floor(impactSpeed * 5);
                const amount = Math.max(1, Math.floor(baseAmount * moneyMultiplier * overallMultiplier));
                awardMoney(amount);
            }
            lastCollisionAt.set(key, nowMs());
            // simple response
            ragdollVel = ragdollVel.scale(-0.3);
        }
    }
});


