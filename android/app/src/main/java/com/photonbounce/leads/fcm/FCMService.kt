package com.photonbounce.leads.fcm

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.os.Build
import androidx.core.app.NotificationCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import com.photonbounce.leads.R
import com.photonbounce.leads.activities.MainActivity

class FCMService : FirebaseMessagingService() {

    companion object {
        const val CHANNEL_ID_LEADS = "new_leads"
        const val CHANNEL_ID_SUMMARY = "daily_summary"
        const val NOTIFICATION_ID_LEAD = 1001
        const val NOTIFICATION_ID_SUMMARY = 1002
    }

    override fun onNewToken(token: String) {
        super.onNewToken(token)
        // TODO: Send token to server
    }

    override fun onMessageReceived(message: RemoteMessage) {
        super.onMessageReceived(message)
        createNotificationChannels()

        val title = message.notification?.title ?: message.data["title"] ?: "Photon Bounce"
        val body = message.notification?.body ?: message.data["body"] ?: ""
        val type = message.data["type"] ?: "lead"

        when (type) {
            "lead" -> showLeadNotification(title, body)
            "summary" -> showSummaryNotification(title, body)
        }
    }

    private fun showLeadNotification(title: String, body: String) {
        val intent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra("navigate_to", "leads")
        }
        val pendingIntent = PendingIntent.getActivity(
            this, 0, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(this, CHANNEL_ID_LEADS)
            .setSmallIcon(R.drawable.ic_leads)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .build()

        getNotificationManager().notify(NOTIFICATION_ID_LEAD, notification)
    }

    private fun showSummaryNotification(title: String, body: String) {
        val intent = Intent(this, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TOP
            putExtra("navigate_to", "analytics")
        }
        val pendingIntent = PendingIntent.getActivity(
            this, 1, intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        val notification = NotificationCompat.Builder(this, CHANNEL_ID_SUMMARY)
            .setSmallIcon(R.drawable.ic_analytics)
            .setContentTitle(title)
            .setContentText(body)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setPriority(NotificationCompat.PRIORITY_DEFAULT)
            .build()

        getNotificationManager().notify(NOTIFICATION_ID_SUMMARY, notification)
    }

    private fun createNotificationChannels() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val leadsChannel = NotificationChannel(
                CHANNEL_ID_LEADS,
                "New Leads",
                NotificationManager.IMPORTANCE_HIGH
            ).apply { description = "Notifications for new incoming leads" }

            val summaryChannel = NotificationChannel(
                CHANNEL_ID_SUMMARY,
                "Daily Summary",
                NotificationManager.IMPORTANCE_DEFAULT
            ).apply { description = "Daily lead summary notifications" }

            getNotificationManager().createNotificationChannels(listOf(leadsChannel, summaryChannel))
        }
    }

    private fun getNotificationManager(): NotificationManager {
        return getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
    }
}
