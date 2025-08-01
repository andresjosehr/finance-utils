import { Head, Link } from '@inertiajs/react'
import AuthenticatedLayout from '@/layouts/authenticated-layout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Progress } from '@/components/ui/progress'
import { ArrowLeft, BarChart3, Activity, Clock, TrendingUp, Database } from 'lucide-react'

interface Statistics {
    total_pairs: number
    active_pairs: number
    inactive_pairs: number
    volume_sampling_enabled: number
    volume_sampling_disabled: number
    pairs_by_fiat: Record<string, number>
    pairs_by_asset: Record<string, number>
    collection_intervals: Record<string, number>
}

interface TradingPair {
    id: number
    pair_symbol: string
    is_active: boolean
    use_volume_sampling: boolean
    collection_interval_minutes: number
    collection_status: any
}

interface Props {
    statistics: Statistics
    pairs: TradingPair[]
}

export default function Statistics({ statistics, pairs }: Props) {
    const getStatusBadge = (pair: TradingPair) => {
        if (!pair.is_active) {
            return <Badge variant="secondary">Inactive</Badge>
        }
        
        const status = pair.collection_status
        const minutesSinceCollection = status.minutes_since_last_collection
        
        if (minutesSinceCollection === null) {
            return <Badge variant="outline">Never Collected</Badge>
        }
        
        if (minutesSinceCollection < 10) {
            return <Badge variant="default">Active</Badge>
        } else if (minutesSinceCollection < 30) {
            return <Badge variant="outline">Recent</Badge>
        } else {
            return <Badge variant="destructive">Stale</Badge>
        }
    }

    const activePercentage = statistics.total_pairs > 0 
        ? (statistics.active_pairs / statistics.total_pairs) * 100 
        : 0

    const volumeSamplingPercentage = statistics.total_pairs > 0 
        ? (statistics.volume_sampling_enabled / statistics.total_pairs) * 100 
        : 0

    return (
        <AuthenticatedLayout>
            <Head title="Trading Pairs Statistics" />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('admin.trading-pairs.index')}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to Trading Pairs
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">Trading Pairs Statistics</h1>
                            <p className="text-muted-foreground">
                                Overview of all trading pairs and their configuration status
                            </p>
                        </div>
                    </div>
                </div>

                {/* Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Pairs</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{statistics.total_pairs}</div>
                            <p className="text-xs text-muted-foreground">
                                Configured trading pairs
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Pairs</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{statistics.active_pairs}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <Progress value={activePercentage} className="flex-1" />
                                <span>{activePercentage.toFixed(1)}%</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Volume Sampling</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{statistics.volume_sampling_enabled}</div>
                            <div className="flex items-center space-x-2 text-xs text-muted-foreground">
                                <Progress value={volumeSamplingPercentage} className="flex-1" />
                                <span>{volumeSamplingPercentage.toFixed(1)}%</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Inactive Pairs</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-gray-500">{statistics.inactive_pairs}</div>
                            <p className="text-xs text-muted-foreground">
                                Not collecting data
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Distribution by Fiat Currency */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Distribution by Fiat Currency</CardTitle>
                            <CardDescription>
                                Number of trading pairs per fiat currency
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {Object.entries(statistics.pairs_by_fiat)
                                .sort(([,a], [,b]) => b - a)
                                .map(([fiat, count]) => {
                                    const percentage = (count / statistics.total_pairs) * 100
                                    return (
                                        <div key={fiat} className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <Badge variant="outline">{fiat}</Badge>
                                                <span className="text-sm font-medium">{count} pairs</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <Progress value={percentage} className="w-24" />
                                                <span className="text-xs text-muted-foreground w-12">
                                                    {percentage.toFixed(1)}%
                                                </span>
                                            </div>
                                        </div>
                                    )
                                })}
                        </CardContent>
                    </Card>

                    {/* Distribution by Asset */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Distribution by Asset</CardTitle>
                            <CardDescription>
                                Number of trading pairs per cryptocurrency asset
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {Object.entries(statistics.pairs_by_asset)
                                .sort(([,a], [,b]) => b - a)
                                .slice(0, 10) // Show top 10
                                .map(([asset, count]) => {
                                    const percentage = (count / statistics.total_pairs) * 100
                                    return (
                                        <div key={asset} className="flex items-center justify-between">
                                            <div className="flex items-center space-x-2">
                                                <Badge variant="outline">{asset}</Badge>
                                                <span className="text-sm font-medium">{count} pairs</span>
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <Progress value={percentage} className="w-24" />
                                                <span className="text-xs text-muted-foreground w-12">
                                                    {percentage.toFixed(1)}%
                                                </span>
                                            </div>
                                        </div>
                                    )
                                })}
                        </CardContent>
                    </Card>
                </div>

                {/* Collection Intervals */}
                <Card>
                    <CardHeader>
                        <CardTitle>Collection Intervals</CardTitle>
                        <CardDescription>
                            Distribution of data collection intervals across trading pairs
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                            {Object.entries(statistics.collection_intervals)
                                .sort(([a], [b]) => parseInt(a) - parseInt(b))
                                .map(([interval, count]) => {
                                    const percentage = (count / statistics.total_pairs) * 100
                                    return (
                                        <div key={interval} className="text-center space-y-2">
                                            <div className="text-2xl font-bold">{count}</div>
                                            <div className="text-sm font-medium">{interval} minutes</div>
                                            <Progress value={percentage} className="w-full" />
                                            <div className="text-xs text-muted-foreground">
                                                {percentage.toFixed(1)}%
                                            </div>
                                        </div>
                                    )
                                })}
                        </div>
                    </CardContent>
                </Card>

                {/* All Trading Pairs Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>All Trading Pairs Status</CardTitle>
                        <CardDescription>
                            Current status of all configured trading pairs
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Trading Pair</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Interval</TableHead>
                                    <TableHead>Volume Sampling</TableHead>
                                    <TableHead>Collection Status</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pairs.map((pair) => (
                                    <TableRow key={pair.id}>
                                        <TableCell>
                                            <Link 
                                                href={route('admin.trading-pairs.show', pair.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {pair.pair_symbol}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={pair.is_active ? "default" : "secondary"}>
                                                {pair.is_active ? "Active" : "Inactive"}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-sm">{pair.collection_interval_minutes}m</span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={pair.use_volume_sampling ? "default" : "outline"}>
                                                {pair.use_volume_sampling ? "Enabled" : "Disabled"}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {getStatusBadge(pair)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    )
}