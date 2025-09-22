<template>
	<div id="tmdb_prefs" class="section">
		<h2>
			<TmdbIcon class="icon" />
			{{ t('integration_tmdb', 'TMDB integration') }}
		</h2>
		<div id="tmdb-content">
			<div class="line">
				<label for="tmdb-api-key-v3">
					<KeyOutlineIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'TMDB API key') }}
				</label>
				<input id="tmdb-api-key-v3"
					v-model="state.api_key_v3"
					type="password"
					:placeholder="t('integration_tmdb', '...')"
					@input="onInput">
			</div>
			<NcNoteCard v-if="state.has_admin_api_key_v3" type="info">
				{{ t('integration_tmdb', 'Leave the API key empty to use the one set by your administrator.') }}
			</NcNoteCard>
			<div class="line">
				<label for="tmdb-api-key-v4">
					<KeyOutlineIcon :size="20" class="icon" />
					{{ t('integration_tmdb', 'TMDB API Read Access Token') }}
				</label>
				<input id="tmdb-api-key-v4"
					v-model="state.api_key_v4"
					type="password"
					:placeholder="t('integration_tmdb', '...')"
					@input="onInput">
			</div>
			<NcNoteCard v-if="state.has_admin_api_key_v4" type="info">
				{{ t('integration_tmdb', 'Leave the API Read Access Token empty to use the one set by your administrator.') }}
			</NcNoteCard>
			<NcNoteCard type="info">
				<a href="https://themoviedb.org" target="_blank" class="external">
					{{ t('integration_tmdb', 'You can create an app and API key in the "API" section of your TMDB account settings.') }}
				</a>
				<p>
					{{ t('integration_tmdb', 'If you set both the API key and the token, the API key will be used in priority.') }}
				</p>
			</NcNoteCard>
			<div id="tmdb-search-block">
				<NcCheckboxRadioSwitch
					:model-value="state.search_enabled"
					@update:model-value="onCheckboxChanged($event, 'search_enabled')">
					{{ t('integration_tmdb', 'Enable searching for movies/persons/series') }}
				</NcCheckboxRadioSwitch>
				<NcNoteCard v-if="state.search_enabled" type="warning">
					{{ t('integration_tmdb', 'Warning, everything you type in the search bar will be sent to TMDB.') }}
				</NcNoteCard>
				<NcCheckboxRadioSwitch
					:model-value="state.link_preview_enabled"
					@update:model-value="onCheckboxChanged($event, 'link_preview_enabled')">
					{{ t('integration_tmdb', 'Enable TMDB/IMDB link previews') }}
				</NcCheckboxRadioSwitch>
			</div>
			<NcCheckboxRadioSwitch
				:model-value="state.navigation_enabled"
				@update:model-value="onCheckboxChanged($event, 'navigation_enabled')">
				{{ t('integration_tmdb', 'Enable navigation link') }}
			</NcCheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import { delay } from '../utils.js'

export default {
	name: 'PersonalSettings',

	components: {
		TmdbIcon,
		NcCheckboxRadioSwitch,
		KeyOutlineIcon,
		NcNoteCard,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_tmdb', 'user-config'),
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
		onCheckboxChanged(newValue, key) {
			this.state[key] = newValue
			this.saveOptions({ [key]: this.state[key] ? '1' : '0' }, false)
		},
		async saveOptions(values, sensitive = true) {
			if (sensitive) {
				await confirmPassword()
			}
			const req = {
				values,
			}
			const url = sensitive
				? generateUrl('/apps/integration_tmdb/sensitive-config')
				: generateUrl('/apps/integration_tmdb/config')
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_tmdb', 'TMDB options saved'))
			}).catch((error) => {
				showError(t('integration_tmdb', 'Failed to save TMDB options'))
				console.debug(error)
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
