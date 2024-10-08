<template>
	<div id="tmdb_prefs" class="section">
		<h2>
			<TmdbIcon class="icon" />
			{{ t('integration_tmdb', 'TMDB integration') }}
		</h2>
		<div id="tmdb-content">
			<div class="line">
				<label for="tmdb-api-key-v3">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'TMDB API key') }}
				</label>
				<input id="tmdb-api-key-v3"
					v-model="state.api_key_v3"
					type="password"
					:placeholder="t('integration_tmdb', '...')"
					@input="onInput">
			</div>
			<div class="line">
				<label for="tmdb-api-key-v4">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'TMDB API Read Access Token') }}
				</label>
				<input id="tmdb-api-key-v4"
					v-model="state.api_key_v4"
					type="password"
					:placeholder="t('integration_tmdb', '...')"
					@input="onInput">
			</div>
			<NcNoteCard type="info">
				<a href="https://themoviedb.org" target="_blank" class="external">
					{{ t('integration_tmdb', 'You can create an app and API key in the "API" section of your TMDB account settings.') }}
				</a>
				<p>
					{{ t('integration_tmdb', 'If you set both the API key and the token, the API key will be used in priority.') }}
				</p>
			</NcNoteCard>
		</div>
	</div>
</template>

<script>
import KeyIcon from 'vue-material-design-icons/Key.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',

	components: {
		TmdbIcon,
		KeyIcon,
		NcNoteCard,
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
	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		margin-top: 12px;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
	}
}
</style>
