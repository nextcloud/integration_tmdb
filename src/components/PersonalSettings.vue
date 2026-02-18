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
			<NcNoteCard v-if="state.has_admin_api_key_v3" type="info">
				{{ t('integration_tmdb', 'Leave the API key empty to use the one set by your administrator.') }}
			</NcNoteCard>
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
			<NcNoteCard v-if="state.has_admin_api_key_v4" type="info">
				{{ t('integration_tmdb', 'Leave the API Read Access Token empty to use the one set by your administrator.') }}
			</NcNoteCard>
			<NcNoteCard type="info">
				<a href="https://themoviedb.org" target="_blank" class="external">
					{{ t('integration_tmdb', 'You can create an app and API key in the "API" section of your TMDB account settings.') }}
				</a>
				<br>
				<span>
					{{ t('integration_tmdb', 'If you set both the API key and the token, the API key will be used in priority.') }}
				</span>
			</NcNoteCard>
			<NcFormBox id="tmdb-search-block">
				<NcFormBoxSwitch
					v-model="state.search_enabled"
					:label="t('integration_tmdb', 'Enable searching for movies/persons/series')"
					@update:model-value="onCheckboxChanged($event, 'search_enabled')" />
				<NcNoteCard v-if="state.search_enabled" type="warning">
					{{ t('integration_tmdb', 'Warning, everything you type in the search bar will be sent to TMDB.') }}
				</NcNoteCard>
				<NcFormBoxSwitch
					v-model="state.link_preview_enabled"
					:label="t('integration_tmdb', 'Enable TMDB/IMDB link previews')"
					@update:model-value="onCheckboxChanged($event, 'link_preview_enabled')" />
			</NcFormBox>
			<NcFormBoxSwitch
				v-model="state.navigation_enabled"
				:label="t('integration_tmdb', 'Enable navigation link')"
				@update:model-value="onCheckboxChanged($event, 'navigation_enabled')" />
		</div>
	</div>
</template>

<script>
import KeyOutlineIcon from 'vue-material-design-icons/KeyOutline.vue'

import TmdbIcon from './icons/TmdbIcon.vue'

import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcTextField from '@nextcloud/vue/components/NcTextField'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

import { delay } from '../utils.js'

export default {
	name: 'PersonalSettings',

	components: {
		KeyOutlineIcon,
		TmdbIcon,
		NcFormBox,
		NcFormBoxSwitch,
		NcNoteCard,
		NcTextField,
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

	h2 {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 8px;
		}
	}
}
</style>
