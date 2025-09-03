// resources/js/components/poster.js
import QRCode from 'qrcode'

export default function poster() {
  return {
    /* -------- Estado inicial -------- */
    code: '',
    phone: '',
    name: '',

    size: 'text-3xl',
    align: 'items-center text-center',

    anversoX: 50,
    anversoY: 92,
    qrX: 16,
    qrY: 59,
    qrSize: 170,

    previewScale: 0.45,
    fitContain: true,

    // límite duro para Y del anverso (evitar que la tarjeta “asome”)
    anversoMaxY: 88,

    // Admin
    rebuildBusy: false,
    rebuildMsg: '',

    // i18n
    i18n: {
      rebuilding: 'Rebuilding…',
      rebuilt: 'Images rebuilt.',
      rebuildFail: 'Could not rebuild images.',
      waHello: 'Hello',
      waInfo: 'I would like information',
      waThanks: 'Thanks'
    },

    /* -------- Internos -------- */
    _onDrag: null, _onStop: null, _dragTarget: null,
    _previewAnv: null, _printAnv: null,
    _previewRev: null, _printRev: null,
    _saveTimer: null,
    _getUrl: null, _postUrl: null,

    /* -------- Lifecycle -------- */
    async init() {
      await this.$nextTick(async () => {
        const d = this.$root.dataset
        this._getUrl  = d.get  || '/poster/state'
        this._postUrl = d.post || '/poster/state'

        this.i18n = {
          rebuilding: d.i18nRebuilding || this.i18n.rebuilding,
          rebuilt:    d.i18nRebuilt    || this.i18n.rebuilt,
          rebuildFail:d.i18nRebuildFail|| this.i18n.rebuildFail,
          waHello:    d.i18nWaHello    || this.i18n.waHello,
          waInfo:     d.i18nWaInfo     || this.i18n.waInfo,
          waThanks:   d.i18nWaThanks   || this.i18n.waThanks
        }

        const hadLocal = this._loadLocal()
        const hadSess  = await this._loadSession()
        if (!hadLocal && !hadSess) this._applyDefaults()

        // Leer CSS vars para swap de fondos
        const anv = this.$root.querySelector('.print-wrapper.bg-anverso')
        const rev = this.$root.querySelector('.print-wrapper.bg-reverso')
        if (anv) {
          const cs = getComputedStyle(anv)
          this._previewAnv = cs.getPropertyValue('--bg-anverso').trim()
          this._printAnv   = cs.getPropertyValue('--bg-anverso-print').trim()
        }
        if (rev) {
          const cs = getComputedStyle(rev)
          this._previewRev = cs.getPropertyValue('--bg-reverso').trim()
          this._printRev   = cs.getPropertyValue('--bg-reverso-print').trim()
        }

        this.renderQr()

        const saveKeys = [
          'name','phone','code','size','align',
          'anversoX','anversoY','qrX','qrY','qrSize',
          'previewScale','fitContain'
        ]
        for (const k of saveKeys) {
          this.$watch(k, () => {
            this._persist()
            if (['name','phone','code','qrSize'].includes(k)) this.renderQr()
          })
        }

        // Clamp dinámico
        this.$watch('anversoX', () => this._clampAnverso())
        this.$watch('anversoY', () => this._clampAnverso())
        this.$watch('qrX',      () => this._clampQr())
        this.$watch('qrY',      () => this._clampQr())

        // Clamp inicial
        this._clampAnverso()
        this._clampQr()

        // Swap HD en impresión
        const before = () => this.usePrintAssets(true)
        const after  = () => this.usePrintAssets(false)
        window.addEventListener('beforeprint', before)
        window.addEventListener('afterprint',  after)
        if (window.matchMedia) {
          const mql = window.matchMedia('print')
          const cb = e => e.matches ? before() : after()
          if (mql.addEventListener) mql.addEventListener('change', cb)
          else if (mql.addListener)  mql.addListener(cb)
        }
      })
    },

    /* -------- Defaults desde controller ($defaults) -------- */
    _applyDefaults() {
      let defs = {}
      try { defs = JSON.parse(this.$root.dataset.defaults || '{}') } catch (_) {}
      if (!defs) return
      const map = {
        preview_scale: 'previewScale',
        fit_contain:   'fitContain',
        size:          'size',
        align:         'align',
        anverso_x:     'anversoX',
        anverso_y:     'anversoY',
        qr_x:          'qrX',
        qr_y:          'qrY',
        qr_size:       'qrSize'
      }
      Object.entries(map).forEach(([k, prop]) => {
        if (defs[k] !== undefined && defs[k] !== null) this[prop] = defs[k]
      })
    },

    /* -------- Persistencia -------- */
    _snapshot() {
      const {
        name, phone, code, size, align,
        anversoX, anversoY, qrX, qrY, qrSize,
        previewScale, fitContain
      } = this
      return { name, phone, code, size, align, anversoX, anversoY, qrX, qrY, qrSize, previewScale, fitContain }
    },
    _loadLocal() {
      try {
        const raw = localStorage.getItem('poster:v2')
        if (raw) { Object.assign(this, JSON.parse(raw)); return true }
      } catch (_) {}
      return false
    },
    async _loadSession() {
      try {
        const resp = await fetch(this._getUrl, { credentials: 'same-origin' })
        if (!resp.ok) return false
        const data = await resp.json()
        if (data && typeof data === 'object' && Object.keys(data).length) { Object.assign(this, data); return true }
      } catch (_) {}
      return false
    },
    _persist() {
      try { localStorage.setItem('poster:v2', JSON.stringify(this._snapshot())) } catch (_) {}
      clearTimeout(this._saveTimer)
      this._saveTimer = setTimeout(() => this._saveSession(), 500)
    },
    async _saveSession() {
      try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        await fetch(this._postUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
          body: JSON.stringify(this._snapshot())
        })
      } catch (_) {}
    },

    /* -------- WhatsApp + QR -------- */
    waUrl() {
      const code = (this.code || '').trim() || '00000000'
      const link = `https://4life.ovh/${encodeURIComponent(code)}`
      const msg  = `*${this.i18n.waHello}*,\n_${this.i18n.waInfo}_:\n> ${link}\n*${this.i18n.waThanks}* 🙂`
      const text = encodeURIComponent(msg)
      const digits = (this.phone || '').replace(/\D/g, '')
      return digits
        ? `https://api.whatsapp.com/send?phone=${digits}&text=${text}`
        : `https://api.whatsapp.com/send?text=${text}`
    },

    _qrAvailableWidth() {
      const box = this.$refs.qrBox
      if (!box) return this.qrSize
      const cs = getComputedStyle(box)
      const padL = parseFloat(cs.paddingLeft) || 0
      const padR = parseFloat(cs.paddingRight) || 0
      return Math.max(16, Math.round(box.clientWidth - padL - padR))
    },

    async renderQr() {
      const el = this.$refs.qr
      if (!el) return
      try {
        const w = this._qrAvailableWidth()
        el.style.width = '100%'
        el.style.height = 'auto'
        await QRCode.toCanvas(el, this.waUrl(), {
          width: w,
          errorCorrectionLevel: 'M',
          margin: 1
        })
      } catch (e) { console.error('Error generando QR:', e) }
    },

    async renderQrHighRes() {
      const el = this.$refs.qr
      if (!el) return
      try {
        const w = this._qrAvailableWidth()
        const pixelRatio = Math.max(2, Math.round(window.devicePixelRatio || 2))
        el.style.width = '100%'
        el.style.height = 'auto'
        await QRCode.toCanvas(el, this.waUrl(), {
          width: Math.round(w * pixelRatio),
          errorCorrectionLevel: 'M',
          margin: 1
        })
      } catch (e) { console.error('Error generando QR HD:', e) }
    },

    /* -------- Swap preview/print -------- */
    usePrintAssets(enable) {
      const anv = this.$root.querySelector('.print-wrapper.bg-anverso')
      const rev = this.$root.querySelector('.print-wrapper.bg-reverso')

      if (enable) {
        if (anv && this._printAnv) anv.style.setProperty('--bg-anverso', this._printAnv)
        if (rev && this._printRev) rev.style.setProperty('--bg-reverso', this._printRev)

        if (this.fitContain) this.$root.classList.add('contain-print')
        else this.$root.classList.remove('contain-print')

        // Re-clamp por seguridad
        this._clampAnverso()
        this._clampQr()

        // Esperar 1 frame para que el layout de @media print esté aplicado
        requestAnimationFrame(() => this.renderQrHighRes())
      } else {
        if (anv && this._previewAnv) anv.style.setProperty('--bg-anverso', this._previewAnv)
        if (rev && this._previewRev) rev.style.setProperty('--bg-reverso', this._previewRev)
        this.$root.classList.remove('contain-print')
        this.renderQr()
      }
    },

    /* -------- UX -------- */
    capitalizeName() {
      this.name = (this.name || '').replace(/\b\p{L}/gu, s => s.toUpperCase())
    },

    /* -------- Drag & drop -------- */
    startDragQr(e)      { e.preventDefault(); this._dragTarget = 'qr';      this._bindDrag(); this._setPosFromEvent(e) },
    startDragAnverso(e) { e.preventDefault(); this._dragTarget = 'anverso'; this._bindDrag(); this._setPosFromEvent(e) },
    _bindDrag() {
      this._onDrag = (ev) => { if (ev.cancelable) ev.preventDefault(); this._setPosFromEvent(ev) }
      this._onStop = () => {
        window.removeEventListener('mousemove', this._onDrag)
        window.removeEventListener('touchmove', this._onDrag)
        window.removeEventListener('mouseup', this._onStop)
        window.removeEventListener('touchend', this._onStop)
        this._dragTarget = null
        this._persist()
      }
      window.addEventListener('mousemove', this._onDrag)
      window.addEventListener('touchmove', this._onDrag, { passive: false })
      window.addEventListener('mouseup', this._onStop)
      window.addEventListener('touchend', this._onStop)
    },
    _setPosFromEvent(e) {
      const isQr = this._dragTarget === 'qr'
      const selector = isQr ? '.print-wrapper.bg-reverso' : '.print-wrapper.bg-anverso'
      const container = this.$root.querySelector(selector)
      if (!container) return

      const rect = container.getBoundingClientRect()
      const p = e.touches ? e.touches[0] : e
      let x = ((p.clientX - rect.left) / rect.width)  * 100
      let y = ((p.clientY - rect.top)  / rect.height) * 100

      if (isQr) {
        ({ x, y } = this._clampWithin(
          '.print-wrapper.bg-reverso',
          () => this.$refs.qrBox?.getBoundingClientRect(),
          x, y
        ))
        this.qrX = x; this.qrY = y
      } else {
        ({ x, y } = this._clampWithin(
          '.print-wrapper.bg-anverso',
          () => this.$refs.anversoBox?.getBoundingClientRect(),
          x, y
        ))
        this.anversoX = x; this.anversoY = y
      }
    },

    /* -------- Admin -------- */
    async rebuildAssets() {
      if (this.rebuildBusy) return
      this.rebuildBusy = true
      this.rebuildMsg = this.i18n.rebuilding
      try {
        const url  = this.$root.dataset.rebuild
        if (!url) throw new Error('No rebuild endpoint.')
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        const res  = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf } })
        if (!res.ok) throw new Error(`HTTP ${res.status}`)
        this._bustBgCache()
        this.rebuildMsg = this.i18n.rebuilt
      } catch (e) {
        console.error(e)
        this.rebuildMsg = this.i18n.rebuildFail
      } finally {
        this.rebuildBusy = false
      }
    },
    _bustBgCache() {
      const bust = (val) => {
        const m = /url\((['"]?)([^'")]+)\1\)/.exec(val || '')
        if (!m) return val
        const base = m[2].split('?')[0]
        return `url("${base}?v=${Date.now()}")`
      }
      const anv = this.$root.querySelector('.print-wrapper.bg-anverso')
      const rev = this.$root.querySelector('.print-wrapper.bg-reverso')
      if (anv) {
        const cs = getComputedStyle(anv)
        anv.style.setProperty('--bg-anverso',       bust(cs.getPropertyValue('--bg-anverso')))
        anv.style.setProperty('--bg-anverso-print', bust(cs.getPropertyValue('--bg-anverso-print')))
      }
      if (rev) {
        const cs = getComputedStyle(rev)
        rev.style.setProperty('--bg-reverso',       bust(cs.getPropertyValue('--bg-reverso')))
        rev.style.setProperty('--bg-reverso-print', bust(cs.getPropertyValue('--bg-reverso-print')))
      }
    },

    /* -------- Clamp helpers -------- */
    _clampWithin(containerSel, getBoxRect, x, y) {
      const container = this.$root.querySelector(containerSel)
      if (!container) return { x, y }
      const crect = container.getBoundingClientRect()
      const brect = getBoxRect?.()
      let halfW = 0, halfH = 0
      if (brect && crect.width && crect.height) {
        halfW = (brect.width  / crect.width)  * 50
        halfH = (brect.height / crect.height) * 50
      }
      x = Math.max(halfW, Math.min(100 - halfW, x))
      const limitY = Math.min(100 - halfH, this.anversoMaxY ?? 100)
      y = Math.max(halfH, Math.min(limitY, y))
      return { x, y }
    },
    _clampAnverso() {
      const { x, y } = this._clampWithin(
        '.print-wrapper.bg-anverso',
        () => this.$refs.anversoBox?.getBoundingClientRect(),
        this.anversoX,
        this.anversoY
      )
      this.anversoX = x; this.anversoY = y
    },
    _clampQr() {
      const { x, y } = this._clampWithin(
        '.print-wrapper.bg-reverso',
        () => this.$refs.qrBox?.getBoundingClientRect(),
        this.qrX,
        this.qrY
      )
      this.qrX = x; this.qrY = y
    },
  }
}
