// 🔐 E2E API Encryption Utilities using Web Crypto API (AES-256-GCM)

const keyHex = import.meta.env.VITE_API_ENCRYPTION_KEY;

function hexToUint8Array(hex) {
  if (!hex || hex.length !== 64) {
    throw new Error('Invalid encryption key length. Expected 64-character hex string (256-bit).');
  }
  const view = new Uint8Array(32);
  for (let i = 0; i < 32; i++) {
    view[i] = parseInt(hex.substring(i * 2, i * 2 + 2), 16);
  }
  return view;
}

function uint8ArrayToBase64(uint8) {
  let binary = '';
  const len = uint8.byteLength;
  for (let i = 0; i < len; i++) {
    binary += String.fromCharCode(uint8[i]);
  }
  return window.btoa(binary);
}

function base64ToUint8Array(base64) {
  const binary = window.atob(base64);
  const len = binary.length;
  const bytes = new Uint8Array(len);
  for (let i = 0; i < len; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes;
}

let cryptoKeyCache = null;

async function getCryptoKey() {
  if (cryptoKeyCache) return cryptoKeyCache;
  const rawKey = hexToUint8Array(keyHex);
  cryptoKeyCache = await window.crypto.subtle.importKey(
    'raw',
    rawKey,
    { name: 'AES-GCM' },
    false,
    ['encrypt', 'decrypt']
  );
  return cryptoKeyCache;
}

/**
 * Encrypt a JSON-serializable object/value using AES-256-GCM
 * @param {*} data 
 * @returns {Promise<string>} Base64 encoded IV + Ciphertext + Tag
 */
export async function encryptPayload(data) {
  try {
    const key = await getCryptoKey();
    const iv = window.crypto.getRandomValues(new Uint8Array(12));
    const plaintext = JSON.stringify(data);
    const encoder = new TextEncoder();
    const plaintextBuffer = encoder.encode(plaintext);

    const encryptedBuffer = await window.crypto.subtle.encrypt(
      {
        name: 'AES-GCM',
        iv: iv,
        tagLength: 128 // 16 bytes tag
      },
      key,
      plaintextBuffer
    );

    // Concatenate IV (12 bytes) and encrypted buffer (ciphertext + 16 bytes tag)
    const combined = new Uint8Array(iv.length + encryptedBuffer.byteLength);
    combined.set(iv, 0);
    combined.set(new Uint8Array(encryptedBuffer), iv.length);

    return uint8ArrayToBase64(combined);
  } catch (error) {
    console.error('Encryption failed:', error);
    throw new Error('Payload encryption failed', { cause: error });
  }
}

/**
 * Decrypt a Base64 encoded payload using AES-256-GCM
 * @param {string} base64Payload 
 * @returns {Promise<*>} Decrypted JSON-parsed object/value
 */
export async function decryptPayload(base64Payload) {
  try {
    const key = await getCryptoKey();
    const combined = base64ToUint8Array(base64Payload);

    if (combined.length < 28) { // 12 bytes IV + at least 16 bytes tag
      throw new Error('Ciphertext too short');
    }

    const iv = combined.slice(0, 12);
    const encryptedData = combined.slice(12);

    const decryptedBuffer = await window.crypto.subtle.decrypt(
      {
        name: 'AES-GCM',
        iv: iv,
        tagLength: 128
      },
      key,
      encryptedData
    );

    const decoder = new TextDecoder();
    const plaintext = decoder.decode(decryptedBuffer);
    return JSON.parse(plaintext);
  } catch (error) {
    console.error('Decryption failed:', error);
    throw new Error('Payload decryption failed', { cause: error });
  }
}
