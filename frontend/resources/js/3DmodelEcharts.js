// resources/js/3dmodelEcharts.js
import * as THREE from 'three';
import { GLTFLoader } from 'three/examples/jsm/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

(() => {
  let renderer, scene, camera, controls, mixer, model, clock;
  let container;
  let ground = null, grid = null;
  let ro = null;
  let rafId = 0;

  let vehicle = null;
  const posSmooth = { x: 0, y: 0, z: 0, k: 0.15 };
  let unitsPerMeter = 1;

  const glbSrc = '/assets/glb/Modelo4.glb';
  let __initToken = 0;

  // ===== Helpers =====
  function pickMainContainer() {
    // AHORA usamos el div de tu Blade: <div id="model3d-main">
    const nodes = Array.from(document.querySelectorAll('#model3d-main'));
    if (!nodes.length) return null;
    let best = null, bestA = -1;
    for (const el of nodes) {
      const cs = getComputedStyle(el);
      if (cs.display === 'none' || cs.visibility === 'hidden') continue;
      const r = el.getBoundingClientRect();
      const a = Math.max(0, r.width) * Math.max(0, r.height);
      if (a > bestA) { bestA = a; best = el; }
    }
    return best || nodes[0];
  }

  function injectGradientBackground() {
    if (!renderer) return;
    renderer.setClearColor(0x000000, 0);
    const id = 'three-3d-gradient-style';
    if (!document.getElementById(id)) {
      const style = document.createElement('style');
      style.id = id;
      style.textContent = `
        #model3d-main {
          background: radial-gradient(120% 80% at 50% 15%, #0b1020 0%, #0f172a 45%, #020617 100%);
        }
      `;
      document.head.appendChild(style);
    }
  }

  function resizeRendererToDisplaySize(force = false) {
    if (!container || !renderer || !camera) return;
    const width = container.clientWidth || 1;
    const height = container.clientHeight || 1;
    const size = renderer.getSize(new THREE.Vector2());
    const needResize = force || size.width !== width || size.height !== height;
    if (needResize) {
      renderer.setSize(width, height, false);
      camera.aspect = width / height;
      camera.updateProjectionMatrix();
    }
  }

  function fitCameraToObject(obj, offset = 2.5) {
    const box0 = new THREE.Box3().setFromObject(obj);
    const size0 = box0.getSize(new THREE.Vector3());
    const center0 = box0.getCenter(new THREE.Vector3());

    obj.position.sub(center0);

    const maxDim = Math.max(size0.x, size0.y, size0.z);
    const fov = camera.fov * (Math.PI / 180);
    let camDist = Math.abs((maxDim / 2) / Math.tan(fov / 2));
    camDist *= offset;

    camera.position.set(0, maxDim * 0.1, camDist);
    camera.lookAt(0, 0, 0);

    controls.target.set(0, 0, 0);
    controls.minDistance = Math.max(maxDim * 0.35, 0.5);
    controls.maxDistance = maxDim * 3.0;
    controls.update();

    return maxDim;
  }

  function placeGroundUnderModel(obj, margin = 2.0) {
    const box = new THREE.Box3().setFromObject(obj);
    const size = box.getSize(new THREE.Vector3());
    const minY = box.min.y;

    const planeSize = Math.max(size.x, size.z) * margin;
    const divisions = Math.max(10, Math.round(planeSize / Math.max(size.x, size.z) * 20));

    if (ground) {
      scene.remove(ground);
      ground.geometry?.dispose?.();
      ground.material?.dispose?.();
    }
    ground = new THREE.Mesh(
      new THREE.PlaneGeometry(planeSize, planeSize),
      new THREE.ShadowMaterial({ opacity: 0.22 })
    );
    ground.rotation.x = -Math.PI / 2;
    ground.position.y = minY - 0.01;
    ground.receiveShadow = true;
    scene.add(ground);

    if (grid) {
      scene.remove(grid);
      grid.geometry?.dispose?.();
      grid.material?.dispose?.();
    }
    grid = new THREE.GridHelper(planeSize, divisions, 0xb9c3d1, 0xe2e8f0);
    grid.material.transparent = true;
    grid.material.opacity = 0.6;
    grid.position.y = minY + 0.001;
    scene.add(grid);
  }

  function animate() {
    const dt = clock.getDelta();
    if (mixer) mixer.update(dt);
    controls.update();
    resizeRendererToDisplaySize();
    renderer.render(scene, camera);
    rafId = requestAnimationFrame(animate);
  }

  function onWindowResize() {
    resizeRendererToDisplaySize(true);
  }

  function destroy3D() {
    try { if (rafId) cancelAnimationFrame(rafId); } catch {}
    rafId = 0;

    try { window.removeEventListener('resize', onWindowResize); } catch {}
    if (ro) { try { ro.disconnect(); } catch {} ro = null; }

    try {
      if (scene) {
        scene.traverse((obj) => {
          if (obj.isMesh) {
            obj.geometry?.dispose?.();
            if (obj.material) {
              if (Array.isArray(obj.material)) obj.material.forEach(m => m.dispose?.());
              else obj.material.dispose?.();
            }
          }
        });
      }
    } catch {}

    try { renderer?.dispose?.(); } catch {}
    try {
      const canvas = renderer?.domElement;
      if (canvas?.parentNode) canvas.parentNode.removeChild(canvas);
    } catch {}

    renderer = scene = camera = controls = mixer = model = clock = null;
    container = ground = grid = vehicle = null;
  }

  // === Mapeo minimal, sin filtros ni ganancia ===
  const ORIENT_MAP = {
    yaw:   { sign: +1 },
    pitch: { sign: +1 },
    roll:  { sign: +1 },
    order: 'YXZ'
  };
  const d2r = (d) => d * Math.PI / 180;

  window.set3DOrientation = function ({ roll = 0, pitch = 0, yaw = 0 } = {}) {
    if (!vehicle) return;

    const norm = (a) => {
      let x = a % 360;
      if (x > 180) x -= 360;
      if (x < -180) x += 360;
      return x;
    };

    const rZ = d2r(norm(roll)  * ORIENT_MAP.roll.sign);
    const pX = d2r(norm(pitch) * ORIENT_MAP.pitch.sign);
    const yY = d2r(norm(yaw)   * ORIENT_MAP.yaw.sign);

    const e = new THREE.Euler(pX, yY, rZ, ORIENT_MAP.order);
    vehicle.quaternion.setFromEuler(e);
  };

  window.set3DPosition = function ({ x = 0, y = 0, z = 0, lerp = true } = {}) {
    if (!vehicle) return;
    if (lerp) {
      posSmooth.x += posSmooth.k * (x - posSmooth.x);
      posSmooth.y += posSmooth.k * (y - posSmooth.y);
      posSmooth.z += posSmooth.k * (z - posSmooth.z);
      vehicle.position.set(posSmooth.x, posSmooth.y, posSmooth.z);
    } else {
      vehicle.position.set(x, y, z);
      posSmooth.x = x; posSmooth.y = y; posSmooth.z = z;
    }
    controls?.target?.copy?.(vehicle.position);
  };

  window.set3DUnitsPerMeter = function (f = 1) {
    unitsPerMeter = (typeof f === 'number' && isFinite(f) && f > 0) ? f : 1;
  };

  window.set3DPositionMetersENU = function ({ east = 0, up = 0, north = 0, lerp = true } = {}) {
    window.set3DPosition({
      x: east * unitsPerMeter,
      y: up   * unitsPerMeter,
      z: north* unitsPerMeter,
      lerp
    });
  };

  // ===== Inicialización =====
  window.inicializarModelo3D = function () {
    if (renderer) destroy3D();

    container = pickMainContainer();
    if (!container) { console.warn('[3D] #model3d-main no encontrado todavía'); return; }

    const rect = container.getBoundingClientRect();
    if (rect.width < 2 || rect.height < 2) {
      setTimeout(() => { try { window.inicializarModelo3D(); } catch(e){ console.warn(e);} }, 60);
      return;
    }

    const myToken = ++__initToken;

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(container.clientWidth, container.clientHeight, false);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    renderer.shadowMap.enabled = true;
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;

    renderer.domElement.style.width = '100%';
    renderer.domElement.style.height = '100%';
    renderer.domElement.style.display = 'block';

    container.innerHTML = '';
    container.appendChild(renderer.domElement);
    injectGradientBackground();

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(45, container.clientWidth / container.clientHeight, 0.1, 10000);
    camera.position.set(0, 1.2, 6);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.08;
    controls.rotateSpeed = 0.9;
    controls.zoomSpeed = 0.8;
    controls.panSpeed = 0.8;
    controls.minDistance = 0.5;
    controls.maxDistance = 1000;
    controls.minPolarAngle = (5 * Math.PI) / 180;
    controls.maxPolarAngle = (175 * Math.PI) / 180;
    controls.autoRotate = false;

    const hemi = new THREE.HemisphereLight(0xffffff, 0xded9ff, 0.7);
    scene.add(hemi);

    const dir = new THREE.DirectionalLight(0xffffff, 1.7);
    dir.position.set(3.5, 6, 4.5);
    dir.castShadow = true;
    dir.shadow.mapSize.set(2048, 2048);
    dir.shadow.radius = 3;
    dir.shadow.camera.near = 0.1;
    dir.shadow.camera.far = 100;
    dir.shadow.camera.left = -15;
    dir.shadow.camera.right = 15;
    dir.shadow.camera.top = 15;
    dir.shadow.camera.bottom = -15;
    scene.add(dir);

    clock = new THREE.Clock();

    if (window.ResizeObserver) {
      try { ro?.disconnect?.(); } catch {}
      ro = new ResizeObserver(() => resizeRendererToDisplaySize(true));
      ro.observe(container);
    }
    window.addEventListener('resize', onWindowResize, { passive: true });

    try {
      const old = scene?.getObjectByName?.('vehicle');
      if (old && scene) scene.remove(old);
    } catch {}

    const loader = new GLTFLoader();
    loader.load(
      glbSrc,
      (gltf) => {
        if (myToken !== __initToken) return;

        model = gltf.scene || gltf.scenes?.[0];
        if (!model) return;

        model.traverse((obj) => {
          if (obj.isMesh) {
            obj.castShadow = true;
            obj.receiveShadow = false;
          }
        });

        const prev = scene.getObjectByName('vehicle');
        if (prev) scene.remove(prev);

        vehicle = new THREE.Group();
        vehicle.name = 'vehicle';
        vehicle.add(model);
        scene.add(vehicle);

        fitCameraToObject(vehicle);
        placeGroundUnderModel(vehicle, 2.2);

        window.set3DOrientation({ roll: 0, pitch: 0, yaw: 0 });
        resizeRendererToDisplaySize(true);
      },
      undefined,
      (err) => {
        if (myToken !== __initToken) return;

        console.error('No se pudo cargar el GLB:', err);
        const geo = new THREE.BoxGeometry(1.6, 1.6, 1.6);
        const mat = new THREE.MeshStandardMaterial({ color: 0x8aa0ff, roughness: 0.35, metalness: 0.05 });
        model = new THREE.Mesh(geo, mat);
        model.castShadow = true;

        const prev = scene.getObjectByName('vehicle');
        if (prev) scene.remove(prev);

        vehicle = new THREE.Group();
        vehicle.name = 'vehicle';
        vehicle.add(model);
        scene.add(vehicle);

        fitCameraToObject(vehicle);
        placeGroundUnderModel(vehicle, 2.2);
        resizeRendererToDisplaySize(true);
      }
    );

    animate();

    window.__three3D = {
      destroy3D,
      get renderer() { return renderer; },
      get scene() { return scene; },
      get camera() { return camera; },
      get controls() { return controls; },
      get model() { return model; },
      get vehicle() { return vehicle; }
    };
  };

  window.reinicializarModelo3D = window.inicializarModelo3D;
})();
