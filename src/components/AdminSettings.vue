<template>
	<div id="tmdb_prefs" class="section">
		<h2>
			<TmdbIcon class="icon" />
			{{ t('integration_tmdb', 'TMDB integration') }}
		</h2>
		<div id="tmdb-content">
			<NcTextField
				v-model="state.api_key_v3"
				type="password"
				:label="t('integration_tmdb', 'TMDB API key')"
				:placeholder="t('integration_tmdb', 'TMDB API key')"
				:show-trailing-button="!!state.api_key_v3"
				@update:model-value="onInput"
				@trailing-button-click="state.api_key_v3 = ''; onInput()">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
			<NcTextField
				v-model="state.api_key_v4"
				type="password"
				:label="t('integration_tmdb', 'TMDB API Read Access Token')"
				:placeholder="t('integration_tmdb', 'TMDB API Read Access Token')"
				:show-trailing-button="!!state.api_key_v4"
				@update:model-value="onInput"
				@trailing-button-click="state.api_key_v4 = ''; onInput()">
				<template #icon>
					<KeyOutlineIcon :size="20" />
				</template>
			</NcTextField>
			<NcNoteCard type="info">
				<a href="https://themoviedb.org" target="_blank" class="external">
					{{ t('integration_tmdb', 'You can create an app and API key in the "API" section of your TMDB account settings.') }}
				</a>
				<br>
				<span>
					{{ t('integration_tmdb', 'If you set both the API key and the token, the API key will be used in priority.') }}
				</span>
			</NcNoteCard>
		</div>
	</div>
</template>

<script>
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',

	components: {
		KeyOutlineIcon,
		NcNoteCard,
		NcTextField,
		TmdbIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_tmdb', 'admin-config'),
			loading: false,
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({
					api_key_v3: this.state.api_key_v3,
					api_key_v4: this.state.api_key_v4,
				})
			}, 2000)()
		},
		async saveOptions(values) {
			await confirmPassword()
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_tmdb/sensitive-admin-config')
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_tmdb', 'TMDB options saved'))
			}).catch((error) => {
				showError(t('integration_tmdb', 'Failed to save TMDB options'))
				console.error(error)
			}).then(() => {
				this.loading = false
			})
		},
	},
}
</script>

<style scoped lang="scss">
#tmdb_prefs {
	#tmdb-content {
		margin-left: 40px;
	}

	h2 {
		display: flex;
		align-items: center;
		margin-top: 12px;
		.icon {
			margin-right: 8px;
		}
	}
}
</style>
