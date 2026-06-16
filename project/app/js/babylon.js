"use strict";
const renderCanvas = document.getElementById('renderCanvas');
const babylonEngine = new BABYLON.Engine(renderCanvas, true, { preserveDrawingBuffer: true, stencil: true });
class SceneManager {
    static create(engine, canvas) {
        const s = new BABYLON.Scene(engine);
        const CAMERA = new BABYLON.TargetCamera("Camera", new BABYLON.Vector3(0, 1008, -22), s);
        s.activeCamera = CAMERA;
        const pipeline = new BABYLON.DefaultRenderingPipeline("pipeline", true, s, [CAMERA]);
        pipeline.imageProcessingEnabled = true;
        pipeline.imageProcessing.vignetteEnabled = false;
        pipeline.imageProcessing.exposure = 0.95;
        pipeline.imageProcessing.contrast = 1.2;
        const hemi = new BABYLON.HemisphericLight('hemi', new BABYLON.Vector3(0, 1, 0), s);
        hemi.intensity = 1.8;
        hemi.groundColor = new BABYLON.Color3(0.4, 0.5, 0.6);
        s.clearColor = new BABYLON.Color4(0.4, 0.7, 1, 1);
        return s;
    }
    static run(scene, engine) {
        engine.runRenderLoop(() => scene.render());
        window.addEventListener('resize', () => engine.resize());
    }
    static createInitialWorld(scene) {
        blockMaterial = new BABYLON.StandardMaterial("blockMat", scene);
        blockMaterial.diffuseColor = new BABYLON.Color3(0.9, 0.4, 0.1);
        blockMaterial.specularColor = new BABYLON.Color3(0, 0, 0);
        generateBlocksChunk(scene, 800, 1000);
    }
}
let blockMaterial;
let activeBlocks = [];
let nextSpawnY = 800;
function generateBlocksChunk(scene, minY, maxY) {
    for (let i = 0; i < 32; i++) {
        const w = Math.random() * 9 + 7;
        const h = 1.2;
        const d = Math.random() * 4 + 3;
        const block = BABYLON.MeshBuilder.CreateBox(`procedural_block_${minY}_${i}`, { width: w, height: h, depth: d }, scene);
        const posX = (Math.random() - 0.5) * 16;
        const posY = minY + Math.random() * (maxY - minY);
        const posZ = 0;
        block.position.set(posX, posY, posZ);
        block.material = blockMaterial;
        block.renderOutline = true;
        block.outlineColor = new BABYLON.Color3(0, 0, 0);
        block.outlineWidth = 0.12;
        const tiltX = 0.395;
        const rotY = 0;
        const tiltZ = (Math.random() > 0.5 ? 1 : -1) * (0.35 + Math.random() * 0.2);
        block.rotation.set(tiltX, rotY, tiltZ);
        activeBlocks.push(block);
    }
}
const scene = SceneManager.create(babylonEngine, renderCanvas);
SceneManager.run(scene, babylonEngine);
const camera = scene.activeCamera;
class RobloxCharacter {
    constructor(scene) {
        this.limbs = [];
        this.limbHitTimers = new Map();
        this.defaultMats = new Map();
        this.hitMaterial = new BABYLON.StandardMaterial("hitMat", scene);
        this.hitMaterial.diffuseColor = new BABYLON.Color3(1, 0.1, 0.1);
        this.hitMaterial.specularColor = new BABYLON.Color3(0, 0, 0);
        this.root = BABYLON.MeshBuilder.CreateBox("r6_torso", { width: 2, height: 2, depth: 1 }, scene);
        const torsoMat = new BABYLON.StandardMaterial("torsoMat", scene);
        torsoMat.diffuseColor = new BABYLON.Color3(0, 0.3, 0.9);
        torsoMat.specularColor = new BABYLON.Color3(0, 0, 0);
        this.root.material = torsoMat;
        this.defaultMats.set("r6_torso", torsoMat);
        const head = BABYLON.MeshBuilder.CreateBox("r6_head", { width: 1.2, height: 1.2, depth: 1.2 }, scene);
        head.position.y = 1.6;
        head.parent = this.root;
        const yellowMat = new BABYLON.StandardMaterial("yellowMat", scene);
        yellowMat.diffuseColor = new BABYLON.Color3(1, 0.8, 0);
        yellowMat.specularColor = new BABYLON.Color3(0, 0, 0);
        head.material = yellowMat;
        this.defaultMats.set("r6_head", yellowMat);
        const leftArm = BABYLON.MeshBuilder.CreateBox("r6_leftArm", { width: 1, height: 2, depth: 1 }, scene);
        leftArm.position.x = -1.6;
        leftArm.parent = this.root;
        leftArm.material = yellowMat;
        this.defaultMats.set("r6_leftArm", yellowMat);
        const rightArm = BABYLON.MeshBuilder.CreateBox("r6_rightArm", { width: 1, height: 2, depth: 1 }, scene);
        rightArm.position.x = 1.6;
        rightArm.parent = this.root;
        rightArm.material = yellowMat;
        this.defaultMats.set("r6_rightArm", yellowMat);
        const leftLeg = BABYLON.MeshBuilder.CreateBox("r6_leftLeg", { width: 1, height: 2, depth: 1 }, scene);
        leftLeg.position.set(-0.5, -2, 0);
        leftLeg.parent = this.root;
        const greenMat = new BABYLON.StandardMaterial("greenMat", scene);
        greenMat.diffuseColor = new BABYLON.Color3(0, 0.7, 0.1);
        greenMat.specularColor = new BABYLON.Color3(0, 0, 0);
        leftLeg.material = greenMat;
        this.defaultMats.set("r6_leftLeg", greenMat);
        const rightLeg = BABYLON.MeshBuilder.CreateBox("r6_rightLeg", { width: 1, height: 2, depth: 1 }, scene);
        rightLeg.position.set(0.5, -2, 0);
        rightLeg.parent = this.root;
        rightLeg.material = greenMat;
        this.defaultMats.set("r6_rightLeg", greenMat);
        this.limbs = [this.root, head, leftArm, rightArm, leftLeg, rightLeg];
        this.limbs.forEach(limb => {
            limb.renderOutline = true;
            limb.outlineColor = new BABYLON.Color3(0, 0, 0);
            limb.outlineWidth = 0.06;
            this.limbHitTimers.set(limb.name, 0);
        });
    }
    triggerFlash(mesh) {
        mesh.material = this.hitMaterial;
        this.limbHitTimers.set(mesh.name, performance.now());
        setTimeout(() => {
            const def = this.defaultMats.get(mesh.name);
            if (def)
                mesh.material = def;
        }, 180);
    }
    updateAnimation(time, velY, velX, isRagdoll) {
        if (isRagdoll) {
            const speedFactor = Math.abs(velY);
            const targetArmZ = Math.min(1.4, speedFactor * 0.09);
            const targetLegX = Math.min(0.6, speedFactor * 0.04);
            this.limbs.forEach((limb) => {
                const lastHitTime = this.limbHitTimers.get(limb.name) || 0;
                const timeSinceHit = performance.now() - lastHitTime;
                if (timeSinceHit < 600) {
                    const decay = (600 - timeSinceHit) / 600;
                    const shake = Math.sin(time * 35) * 0.8 * decay;
                    if (limb.name.endsWith("Arm") || limb.name.endsWith("Leg")) {
                        limb.rotation.x = shake;
                        limb.rotation.z = Math.cos(time * 30) * 0.4 * decay;
                    }
                }
                else {
                    if (limb.name === "r6_leftArm") {
                        limb.rotation.set(0, 0, targetArmZ);
                    }
                    else if (limb.name === "r6_rightArm") {
                        limb.rotation.set(0, 0, -targetArmZ);
                    }
                    else if (limb.name.endsWith("Leg")) {
                        limb.rotation.set(targetLegX, 0, 0);
                    }
                    else if (limb.name === "r6_head") {
                        limb.rotation.set(Math.sin(time * 2) * 0.05, 0, 0);
                    }
                }
            });
            this.root.rotation.z = -velX * 0.12;
            this.root.rotation.x = Math.min(0.3, speedFactor * 0.02);
        }
        else {
            this.limbs.forEach(l => l.rotation.set(0, 0, 0));
            this.root.rotation.set(0, 0, 0);
        }
    }
}
let health = 100;
let isDead = false;
let ragdollMode = false;
let flightMode = false;
let ragdollPos = new BABYLON.Vector3(0, 1002, 0);
let ragdollVel = new BABYLON.Vector3(0, 0, 0);
const lastCollisionAt = new Map();
const visualCharacter = new RobloxCharacter(scene);
visualCharacter.root.position.copyFrom(ragdollPos);
SceneManager.createInitialWorld(scene);
const inputMap = {};
window.addEventListener("keydown", (e) => { inputMap[e.key.toLowerCase()] = true; });
window.addEventListener("keyup", (e) => { inputMap[e.key.toLowerCase()] = false; });
let balance = window.INITIAL_BALANCE || 0;
let moneyMultiplier = parseFloat(localStorage.getItem('moneyMultiplier') || '1');
let speedMultiplier = parseFloat(localStorage.getItem('speedMultiplier') || '1');
let overallMultiplier = parseFloat(localStorage.getItem('overallMultiplier') || '1');
let eventMultiplier = 1;
window.addEventListener("gameEvent", (e) => {
    const data = e.detail;
    if (data.active) {
        eventMultiplier = data.multiplier;
        scene.clearColor = new BABYLON.Color4(0.2, 0.0, 0.35, 1.0);
    }
    else {
        eventMultiplier = 1;
        scene.clearColor = new BABYLON.Color4(0.4, 0.7, 1, 1);
    }
});
const hpDisplay = document.createElement("div");
hpDisplay.style.position = "absolute";
hpDisplay.style.top = "60px";
hpDisplay.style.left = "20px";
hpDisplay.style.color = "#ff3333";
hpDisplay.style.fontFamily = "Arial, sans-serif";
hpDisplay.style.fontSize = "24px";
hpDisplay.style.fontWeight = "bold";
hpDisplay.style.textShadow = "2px 2px 4px #000000";
hpDisplay.style.zIndex = "1002";
hpDisplay.innerText = "HP: 100";
document.body.appendChild(hpDisplay);
function updateHPDisplay() {
    hpDisplay.innerText = `HP: ${Math.max(0, health)}`;
    if (health <= 0) {
        hpDisplay.innerText = "STATUS: WASTED";
    }
}
function setupRandomEvents() {
    setInterval(() => {
        if (!ragdollMode && Math.random() > 0.5) {
            window.dispatchEvent(new CustomEvent('gameEvent', { detail: { active: true, multiplier: 3 } }));
            setTimeout(() => {
                window.dispatchEvent(new CustomEvent('gameEvent', { detail: { active: false, multiplier: 1 } }));
            }, 10000);
        }
    }, 30000);
}
setupRandomEvents();
function spawnFloatingMoneyText(scene, pos, amount) {
    const dynamicTexture = new BABYLON.DynamicTexture("moneyTex", { width: 160, height: 80 }, scene, false);
    dynamicTexture.hasAlpha = true;
    dynamicTexture.drawText(`+€${amount}`, null, null, "bold 28px Arial", "#55ff55", "transparent", true);
    const plane = BABYLON.MeshBuilder.CreatePlane("floatingText", { width: 3, height: 1.5 }, scene);
    plane.position.copyFrom(pos);
    plane.position.z -= 1;
    plane.billboardMode = BABYLON.Mesh.BILLBOARDMODE_ALL;
    const mat = new BABYLON.StandardMaterial("textMat", scene);
    mat.diffuseTexture = dynamicTexture;
    mat.emissiveColor = new BABYLON.Color3(1, 1, 1);
    mat.backFaceCulling = false;
    plane.material = mat;
    let frame = 0;
    const observer = scene.onBeforeRenderObservable.add(() => {
        plane.position.y += 0.05;
        frame++;
        if (frame > 45) {
            scene.onBeforeRenderObservable.remove(observer);
            plane.dispose();
            mat.dispose();
            dynamicTexture.dispose();
        }
    });
}
function awardMoney(amount, pos) {
    amount = Math.max(0, Math.floor(amount));
    if (amount === 0)
        return;
    const finalAmount = Math.max(0, Math.floor(amount * moneyMultiplier * overallMultiplier * eventMultiplier));
    if (finalAmount === 0)
        return;
    spawnFloatingMoneyText(scene, pos, finalAmount);
    const fd = new FormData();
    fd.append('action', 'add_money');
    fd.append('amount', String(finalAmount));
    fetch('./main.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
        if (data.success) {
            balance = data.balance;
            const el = document.getElementById('balance-amount');
            if (el)
                el.innerText = String(balance);
        }
    }).catch(() => {
        balance += finalAmount;
        const el = document.getElementById('balance-amount');
        if (el)
            el.innerText = String(balance);
    });
}
function handleDeathAndRespawn() {
    isDead = true;
    ragdollVel.set(0, 0, 0);
    setTimeout(() => {
        activeBlocks.forEach(b => b.dispose());
        activeBlocks = [];
        nextSpawnY = 800;
        health = 100;
        isDead = false;
        ragdollMode = false;
        ragdollPos.set(0, 1002, 0);
        ragdollVel.set(0, 0, 0);
        visualCharacter.root.position.copyFrom(ragdollPos);
        visualCharacter.limbs.forEach(l => l.rotation.set(0, 0, 0));
        updateHPDisplay();
        SceneManager.createInitialWorld(scene);
    }, 3000);
}
window.addEventListener('keydown', (e) => {
    if (e.key.toLowerCase() === 'r' && !isDead && !flightMode) {
        if (!ragdollMode) {
            ragdollMode = true;
            ragdollVel = new BABYLON.Vector3(0, -5, 0).scale(speedMultiplier);
        }
    }
    if (e.shiftKey && e.key.toLowerCase() === 'p') {
        flightMode = !flightMode;
    }
});
scene.onBeforeRenderObservable.add(() => {
    const dt = babylonEngine.getDeltaTime() / 1000 || 1 / 60;
    if (flightMode) {
        const flySpeed = 40 * dt;
        if (inputMap["w"])
            camera.position.y += flySpeed;
        if (inputMap["s"])
            camera.position.y -= flySpeed;
        if (inputMap["a"])
            camera.position.x -= flySpeed;
        if (inputMap["d"])
            camera.position.x += flySpeed;
        return;
    }
    if (!ragdollMode) {
        ragdollPos.set(0, 1002, 0);
        ragdollVel.set(0, 0, 0);
        visualCharacter.root.position.copyFrom(ragdollPos);
        visualCharacter.updateAnimation(performance.now() * 0.001, 0, 0, false);
        camera.position.set(0, 1006, -14);
        camera.setTarget(ragdollPos.add(new BABYLON.Vector3(0, 1, 0)));
    }
    else {
        if (!isDead) {
            const gravity = new BABYLON.Vector3(0, -11.0, 0);
            ragdollVel.addInPlace(gravity.scale(dt));
            if (ragdollVel.y < -16) {
                ragdollVel.y = -16;
            }
            ragdollVel.z = 0;
            ragdollPos.z = 0;
            if (ragdollPos.x > 11) {
                ragdollVel.x -= 22 * dt;
            }
            else if (ragdollPos.x < -11) {
                ragdollVel.x += 22 * dt;
            }
            ragdollVel.x *= Math.max(0, 1 - 0.5 * dt);
        }
        ragdollPos.addInPlace(ragdollVel.scale(dt));
        visualCharacter.root.position.copyFrom(ragdollPos);
        if (ragdollPos.y < nextSpawnY + 100) {
            generateBlocksChunk(scene, nextSpawnY - 200, nextSpawnY);
            const cleanThreshold = ragdollPos.y + 150;
            activeBlocks = activeBlocks.filter(b => {
                if (b.position.y > cleanThreshold) {
                    b.dispose();
                    return false;
                }
                return true;
            });
            nextSpawnY -= 200;
        }
        visualCharacter.updateAnimation(performance.now() * 0.001, ragdollVel.y, ragdollVel.x, true);
        camera.position.copyFrom(ragdollPos.add(new BABYLON.Vector3(0, 5, -12)));
        camera.setTarget(ragdollPos);
        if (!isDead) {
            for (const block of activeBlocks) {
                if (!block.getBoundingInfo)
                    continue;
                const blockBBox = block.getBoundingInfo().boundingBox;
                for (const limb of visualCharacter.limbs) {
                    const limbKey = `${block.name}_${limb.name}`;
                    const lastHit = lastCollisionAt.get(limbKey) || 0;
                    if (performance.now() - lastHit < 140)
                        continue;
                    const dist = BABYLON.Vector3.Distance(limb.getAbsolutePosition(), blockBBox.centerWorld);
                    const hitThreshold = 1.6;
                    if (dist < hitThreshold) {
                        const impactSpeed = ragdollVel.length();
                        if (impactSpeed > 2.0) {
                            visualCharacter.triggerFlash(limb);
                            const damage = Math.floor(impactSpeed * 0.16);
                            health -= damage;
                            updateHPDisplay();
                            const baseAmount = Math.floor(impactSpeed * 35);
                            awardMoney(baseAmount, limb.getAbsolutePosition());
                            if (health <= 0) {
                                handleDeathAndRespawn();
                                return;
                            }
                        }
                        lastCollisionAt.set(limbKey, performance.now());
                        const blockTilt = block.rotation.z;
                        ragdollVel.y = Math.abs(ragdollVel.y) * 0.2 + 2.0;
                        ragdollVel.x = blockTilt * -24 + (Math.random() - 0.5) * 2;
                    }
                }
            }
        }
    }
});
