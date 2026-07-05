package com.photonbounce.leads

import android.app.Application
import com.google.android.gms.ads.MobileAds
import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.api.RetrofitClient
import com.photonbounce.leads.db.AppDatabase
import com.photonbounce.leads.utils.NetworkMonitor
import com.photonbounce.leads.utils.TokenManager

class LeadsApplication : Application() {

    lateinit var tokenManager: TokenManager
        private set
    lateinit var api: LeadsApiService
        private set
    lateinit var database: AppDatabase
        private set
    lateinit var networkMonitor: NetworkMonitor
        private set

    override fun onCreate() {
        super.onCreate()

        tokenManager = TokenManager(this)
        api = RetrofitClient.create(tokenManager)
        database = AppDatabase.getInstance(this)
        networkMonitor = NetworkMonitor(this)

        // Initialize AdMob
        MobileAds.initialize(this) {}
    }
}
