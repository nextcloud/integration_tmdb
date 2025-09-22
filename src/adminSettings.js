/**
 * Nextcloud - Tmdb
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2023
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

const app = createApp(AdminSettings)
app.mixin({ methods: { t, n } })
app.mount('#tmdb_prefs')
